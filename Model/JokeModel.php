<?php

declare(strict_types=1);

namespace Model;

use Mysql\Database;
use PDO;
use PDOException;

class JokeModel
{
    public function getJokesFromDatabase(int $userId = 1): array
    {
        try {
            $db = (new Database())->getConnection();
            $query = <<<'SQL'
SELECT j.id,
       j.joke_text,
       c.category AS category,
       j.date_created,
       CASE WHEN uf.id IS NOT NULL THEN 1 ELSE 0 END AS is_favourite
FROM jokes j
LEFT JOIN categories c ON j.category_id = c.id
LEFT JOIN user_favourites uf ON j.id = uf.joke_id AND uf.user_id = :user_id
ORDER BY j.id DESC
SQL;

            $statment = $db->prepare($query);
            $statment->execute(['user_id' => $userId]);

            return $statment->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching jokes: " . $e->getMessage());
            return [];
        }
    }

    public function jokeExists(string $jokeText, ?int $excludeId = null): bool
    {
        try {
            return $this->getJokeIdByText($jokeText, $excludeId) !== null;
        } catch (PDOException $e) {
            error_log("Error checking joke existence: " . $e->getMessage());
            return false;
        }
    }

    public function addJokeToDatabase(string $joke, string $category): bool
    {
        if ($this->jokeExists($joke)) {
            throw new \Exception("Joke already exists."); 
        }

        $db = (new Database())->getConnection();
        $categoryId = $this->getOrCreateCategoryId($category);

        $query = <<<'SQL'
    INSERT INTO jokes (joke_text, category_id, date_created)
    VALUES (:joke_text, :category_id, NOW())
    SQL;

        $statment = $db->prepare($query);
        return $statment->execute([
            'joke_text'   => $joke,
            'category_id' => $categoryId,
        ]);
    }

    public function getFavouriteJokes(int $userId): array
    {
        try {
            $db = (new Database())->getConnection();
            $query = <<<'SQL'
SELECT j.id,
       j.joke_text,
       c.category AS category,
       j.date_created
FROM jokes j
JOIN user_favourites uf ON j.id = uf.joke_id
LEFT JOIN categories c ON j.category_id = c.id
WHERE uf.user_id = :user_id
ORDER BY j.id DESC
SQL;

            $statment = $db->prepare($query);
            $statment->execute(['user_id' => $userId]);

            return $statment->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching favourite jokes: " . $e->getMessage());
            return [];
        }
    }

    public function addToFavourites(int $userId, int $jokeId): bool
    {
        try {
            $db = (new Database())->getConnection();
            $query = <<<'SQL'
INSERT IGNORE INTO user_favourites (user_id, joke_id, date_created)
VALUES (:user_id, :joke_id, NOW())
SQL;

            $statment = $db->prepare($query);
            $statment->execute([
                'user_id' => $userId,
                'joke_id' => $jokeId,
            ]);

            return $statment->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error adding to favourites: " . $e->getMessage());
            return false;
        }
    }

    public function removeFromFavourites(int $userId, int $jokeId): bool
    {
        try {
            $db = (new Database())->getConnection();
            $query = <<<'SQL'
DELETE FROM user_favourites
WHERE user_id = :user_id AND joke_id = :joke_id
SQL;
            $statement = $db->prepare($query);
            return $statement->execute([
                'user_id' => $userId,
                'joke_id' => $jokeId,
            ]);
        } catch (PDOException $e) {
            error_log("Error removing from favourites: " . $e->getMessage());
            return false;
        }
    }

    public function isJokeInFavourites(int $userId, int $jokeId): bool
    {
        try {
            $db = (new Database())->getConnection();
            $query = "SELECT * FROM user_favourites WHERE user_id = :user_id AND joke_id = :joke_id";
            $statement = $db->prepare($query);
            $statement->execute([
                'user_id' => $userId,
                'joke_id' => $jokeId
            ]);
            return $statement->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking if joke is in favourites: " . $e->getMessage());
            return false;
        }
    }

    public function getJokeIdByText(string $jokeText, ?int $excludeId = null): ?int
    {
        try {
            $db = (new Database())->getConnection();
            $query = <<<'SQL'
SELECT id FROM jokes
WHERE joke_text = :joke_text
SQL;

            $params = ['joke_text' => $jokeText];

            if (!empty($excludeId)) {
                $query .= ' AND id != :exclude_id';
                $params['exclude_id'] = $excludeId;
            }

            $statment = $db->prepare($query);
            $statment->execute($params);

            $result = $statment->fetchColumn();

            return $result !== false ? (int)$result : null;
        } catch (PDOException $e) {
            error_log("Error checking joke by text: " . $e->getMessage());
            return null;
        }
    }

    public function addJokeAndReturnId(string $joke, string $category): ?int
    {
        try {
            $existingId = $this->getJokeIdByText($joke);
            if (!empty($existingId)) {
                return $existingId;
            }

            $db = (new Database())->getConnection();
            $categoryId = $this->getOrCreateCategoryId($category);

            $query = <<<'SQL'
INSERT INTO jokes (joke_text, category_id, date_created)
VALUES (:joke_text, :category_id, NOW())
SQL;

            $statment = $db->prepare($query);
            $statment->execute([
                'joke_text'   => $joke,
                'category_id' => $categoryId,
            ]);

            return (int)$db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error inserting joke: " . $e->getMessage());
            return null;
        }
    }

    public function getSavedJokesByUser(int $userId): array
    {
        try {
            $db = (new Database())->getConnection();
            $query = <<<'SQL'
SELECT j.id,
       j.joke_text,
       c.category AS category,
       j.date_created
FROM jokes j
LEFT JOIN categories c ON j.category_id = c.id
ORDER BY j.id DESC
SQL;

            $statment = $db->prepare($query);
            $statment->execute();

            return $statment->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching saved jokes: " . $e->getMessage());
            return [];
        }
    }

    public function deleteJokeById(int $jokeId): bool
    {
        try {
            $db = (new Database())->getConnection();

            $db->prepare('DELETE FROM user_favourites WHERE joke_id = :id')->execute(['id' => $jokeId]);

            $stmt = $db->prepare('DELETE FROM jokes WHERE id = :id');
            $stmt->execute(['id' => $jokeId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting joke: " . $e->getMessage());
            return false;
        }
    }

    private function getOrCreateCategoryId(string $category): int
    {
        $db = (new Database())->getConnection();

        $statment = $db->prepare('SELECT id FROM categories WHERE category = :category');
        $statment->execute(['category' => $category]);

        $categoryId = $statment->fetchColumn();

        if (!$categoryId) {
            $statment = $db->prepare('INSERT INTO categories (category) VALUES (:category)');
            $statment->execute(['category' => $category]);
            $categoryId = (int)$db->lastInsertId();
        }

        return (int)$categoryId;
    }
}
