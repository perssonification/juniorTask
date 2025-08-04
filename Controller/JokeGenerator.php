<?php
require_once __DIR__ . '/../Model/JokeModel.php';
require_once __DIR__ . '/../Mysql/Database.php';

use Model\JokeModel;

class JokeController
{
    private JokeModel $model;

    public function __construct()
    {
        $this->model = new JokeModel();
    }

    public function handleRequest(): void
    {
        header('Content-Type: application/json');

        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $action = $_REQUEST['action'] ?? '';
            $userId = 1; 

            if ($method === 'GET' && $action === 'fetch_jokes') {
                $jokes = $this->model->getJokesFromDatabase($userId);
                echo json_encode(['data' => $jokes]);
                return;
            }
            if ($method === 'GET' && $action === 'fetch_favourites') {
                $jokes = $this->model->getFavouriteJokes($userId);
                echo json_encode(['data' => $jokes]);
                return;
            }

            if ($method === 'GET' && $action === 'fetch_saved') {
                $jokes = $this->model->getSavedJokesByUser($userId);
                echo json_encode(['data' => $jokes]);
                return;
            }

            if ($method === 'POST') {
                switch ($action) {

                    case 'fetchJoke':
                        $category = $_POST['category'] ?? null;
                        $url = $category
                            ? "https://api.chucknorris.io/jokes/random?category=" . urlencode($category)
                            : "https://api.chucknorris.io/jokes/random";

                        $data = @file_get_contents($url);
                        if ($data === false) {
                            echo json_encode(['error' => 'Failed to fetch joke from API.']);
                            return;
                        }

                        $decoded = json_decode($data, true);
                        $jokeText = $decoded['value'] ?? 'No joke found.';

                        $existingId = $this->model->getJokeIdByText($jokeText);

                        echo json_encode([
                            'joke' => $jokeText,
                            'joke_id' => $existingId
                        ]);
                        return;

                    case 'addJoke':
                        $category = $_POST['category'] ?? 'uncategorized';
                        $joke = $_POST['joke'] ?? null;

                        if (!$joke) {
                            echo json_encode(['error' => 'Joke text missing']);
                            return;
                        }

                        try {
                            $this->model->addJokeToDatabase($joke, $category);
                            echo json_encode(['success' => true]);
                        } catch (Exception $e) {
                            if ($e->getMessage() === 'Joke already exists.') {
                                echo json_encode(['error' => 'Joke already exists.']);
                            } else {
                                echo json_encode(['error' => 'Failed to save joke.']);
                            }
                        }
                        return;

                    case 'addToFavourites':
                        $jokeId = $_POST['joke_id'] ?? null;
                        if (!$jokeId) {
                            echo json_encode(['error' => 'Joke ID is required.']);
                            return;
                        }

                        $inserted = $this->model->addToFavourites($userId, (int)$jokeId);

                        echo json_encode([
                            'success' => true,
                            'already_favourited' => !$inserted
                        ]);
                        return;

                    case 'deleteJoke':
                        $jokeId = $_POST['joke_id'] ?? null;
                        if (!$jokeId) {
                            echo json_encode(['error' => 'Missing joke ID.']);
                            return;
                        }

                        $deleted = $this->model->deleteJokeById((int)$jokeId);
                        echo json_encode(['success' => $deleted]);
                        return;

                    case 'addJokeAndFavourite':
                        $jokeText = $_POST['joke'] ?? null;
                        $category = $_POST['category'] ?? 'uncategorized';

                        if (!$jokeText) {
                            echo json_encode(['error' => 'Joke is missing.']);
                            return;
                        }

                        $jokeId = $this->model->getJokeIdByText($jokeText);

                        if (!$jokeId) {
                            $jokeId = $this->model->addJokeAndReturnId($jokeText, $category);
                            if (!$jokeId) {
                                echo json_encode(['error' => 'Failed to insert joke.']);
                                return;
                            }
                        }

                        $added = $this->model->addToFavourites($userId, $jokeId);

                        echo json_encode([
                            'success' => true,
                            'already_favourited' => !$added
                        ]);
                        return;
                        case 'removeFromFavourites':
                            $jokeId = $_POST['joke_id'] ?? null;

                            if (!$jokeId) {
                                echo json_encode(['error' => 'missing joke ID.']);
                                return;
                            }

                            $removed = $this->model->removeFromFavourites($userId, (int)$jokeId);

                            echo json_encode(['success' => $removed]);
                            return;

                        case 'checkFavouriteStatus':
                            $jokeId = $_POST['joke_id'] ?? null;

                            if (!$jokeId) {
                                echo json_encode(['error' => 'missing joke ID.']);
                                return;
                            }

                            $isFavourite = $this->model->isJokeInFavourites($userId, (int)$jokeId);

                            echo json_encode(['isFavourite' => $isFavourite]);
                            return;

                }
            }

            echo json_encode(['error' => 'unsupported request.']);
        } catch (Exception $e) {
            echo json_encode([
                'error' => 'Server error occurred please try later.',
                'message' => $e->getMessage()
            ]);
        }
    }
}

$controller = new JokeController();
$controller->handleRequest();
