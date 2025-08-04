<?php
$categories = json_decode(file_get_contents("https://api.chucknorris.io/jokes/categories"), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Chuck Norris Joke Generator</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    .tab-content > div { display: none; }
    .tab-content > div.active { display: block; }
  </style>

</head>

<body class="context">
  <nav class="navbar navbar-expand-lg navbar-light bg-light px-4">
    <a class="navbar-brand">Chuck Norris</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link tab-link active" href="#" data-tab="home">Home Page</a>
        </li>
        <li class="nav-item">
          <a class="nav-link tab-link" href="#" data-tab="fav">Favourites</a>
        </li>
        <li class="nav-item">
          <a class="nav-link tab-link" href="#" data-tab="saved">Saved Jokes</a>
        </li>
      </ul>
    </div>
  </nav>

  <div class="tab-content">
    <div class="d-flex justify-content-center my-5">
      <div class="tab-pane active" id="home">
        <div class="mainBox row justify-content-center" id="jokeBox">
          <div class="header d-flex justify-content-center">
            <h1>Chuck Norris Joke Generator</h1>
          </div>

          <div class="my-4 d-flex justify-content-center">
            <strong>Selected Category:</strong> <span id="selectedCategory" class="ms-2">None</span>
          </div>

          <div class="box d-flex justify-content-center flex-wrap">
            <button id="getJokeBtn" class="btn btn-primary mx-2 my-1">Get Random Joke</button>
            <button id="nextJokeBtn" class="btn btn-secondary mx-2 my-1" disabled>Next Joke</button>
            <div class="dropdown mx-2 my-1">
              <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                Joke by Category
              </button>
              <ul class="dropdown-menu" id="categoryDropdown"></ul>
            </div>
          </div>

          <div class="joke d-flex justify-content-center my-5">
            <div id="jokeResult" class="alert alert-info" style="display: none;"></div>
          </div>
          <div class="d-flex justify-content-center flex-wrap">
            <button id="addJokebtn" class="btn btn-primary mx-2 my-1" >Save Joke</button>
            <button id="addFavbtn" class="btn btn-primary mx-2 my-1 bi-star"></button>
          </div>
        </div>
      </div>
    </div>

  <div class="content mt-4 tab-pane" id="fav">
    <h2>Favourite Jokes</h2>
    <table class="data-table table table-hover mt-4 overflow-auto" id="favTable">
      <thead>
          <tr>
              <th>Id</th>
              <th>Category</th>
              <th>Joke</th>
              <th>Date Created</th>
              <th>Actions</th>
          </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <div class="content mt-4 tab-pane" id="saved">
    <h2>Saved Jokes</h2>
    <table class="data-table table table-hover mt-4 overflow-auto" id="savedTable">
      <thead>
          <tr>
              <th>Id</th>
              <th>Category</th>
              <th>Joke</th>
              <th>Date Created</th>
              <th>Actions</th>
          </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <script>
    let selectedCategory = null;
    let categories = <?php echo json_encode($categories); ?>;
    let currentJoke = null;
    let dataTable = null;

    $(document).ready(function () {
      $('#jokeBox').show();

      $('.tab-link').click(function(e) {
        e.preventDefault();
        const tab = $(this).data('tab');

        $('.tab-link').removeClass('active');
        $(this).addClass('active');
        $('.tab-content > div').removeClass('active');
        $('#' + tab).addClass('active');

        if (tab === 'home') {
          $('#jokeBox').show();
        } else {
          $('#jokeBox').hide();
        }

        if (tab === 'fav') {
          if (!$.fn.DataTable.isDataTable('#favTable')) {
            initFavouritesTable();
          } else {
            favDataTable.ajax.reload();
          }
        } else if (tab === 'saved') {
          if (!$.fn.DataTable.isDataTable('#savedTable')) {
            initSavedTable();
          } else {
            $('#savedTable').DataTable().ajax.reload();
          }
        }
      });

      let $getJokeBtn = $('#getJokeBtn');
      let $nextJokeBtn = $('#nextJokeBtn');
      let $jokeResult = $('#jokeResult');
      let $selectedCategoryText = $('#selectedCategory');
      let $dropdown = $('#categoryDropdown');
      let currentJokeId = null;

      let dropdownHTML = '';
      categories.forEach(cat => {
        dropdownHTML += `
          <li>
            <a class="dropdown-item category-link" href="#" data-category="${cat}">
              ${cat.charAt(0).toUpperCase() + cat.slice(1)}
            </a>
          </li>`;
      });
      $dropdown.html(dropdownHTML);

      $dropdown.on('click', '.category-link', function (e) {
        e.preventDefault();
        selectedCategory = $(this).data('category');
        $selectedCategoryText.text(selectedCategory);
        $nextJokeBtn.prop('disabled', false);
        fetchJoke(selectedCategory);
      });

      function fetchJoke(category = null) {
        $getJokeBtn.prop('disabled', true);
        $nextJokeBtn.prop('disabled', true);
        $jokeResult.hide();

        $.ajax({
          url: '/Controller/JokeGenerator.php',
          type: 'POST',
          data: {
            action: 'fetchJoke',
            category: category
          },
          dataType: 'json',
          success: function (response) {
            if (response.error) {
              alert('Error: ' + response.error);
              return;
            }

            currentJoke = response.joke;
            currentJokeId = response.joke_id ?? null;

            $jokeResult.text(currentJoke).show();
            $getJokeBtn.prop('disabled', false);
            if (category) {
              $nextJokeBtn.prop('disabled', false);
            }
          }
        });
      }
      $getJokeBtn.click(function () {
        selectedCategory = null;

        $selectedCategoryText.text('None');
        $nextJokeBtn.prop('disabled', true);
        fetchJoke();
      });

      $nextJokeBtn.click(function () {
        if (selectedCategory) {
          fetchJoke(selectedCategory);
        }
      });

      $('#addJokebtn').click(function () {
        if (!currentJoke) {
          alert("Please select a joke first.");
          return;
        }

        $.ajax({
          url: '/Controller/JokeGenerator.php',
          type: 'POST',
          data: {
            action: 'addJoke',
            category: selectedCategory ?? 'uncategorized',
            joke: currentJoke
          },
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              alert('Joke saved!');
              dataTable.ajax.reload();
            } else if (response.error === 'Joke already exists.') {
              alert('This joke already exists in the database.');
            } 
          },
          error: function () {
            alert('Failed to add joke.');
          }
        });
      });

      $('#addFavbtn').click(function () {
        if (!currentJoke) {
          alert("Please get a joke first.");
          return;
        }
        $.ajax({
          url: '/Controller/JokeGenerator.php',
          type: 'POST',
          data: {
            action: 'addJokeAndFavourite',
            joke: currentJoke,
            category: selectedCategory ?? 'uncategorized'
          },
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              if (response.already_favourited) {
                alert('You already favourited this joke.');
              } else {
                alert('Joke added to favourites!');
                if (favDataTable) favDataTable.ajax.reload();
              }
            } else {
              alert(response.error || 'Something went wrong.');
            }
          },
          error: function () {
            alert('Request failed.');
          }
        });
      });

      function updateFavButtonState(isFavourited) {
        let $btn = $('#addFavbtn');
        $btn.toggleClass('btn-warning', isFavourited);
        $btn.toggleClass('btn-primary', !isFavourited);
      }

      function addToFavourites(jokeId) {
        $.ajax({
          url: '/Controller/JokeGenerator.php',
          type: 'POST',
          data: {
            action: 'addToFavourites',
            joke_id: jokeId
          },
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              if (response.already_favourited) {
                alert('You already favourited this joke.');
              } else {
                alert('Added to favourites!');
              }
            }
          },
          error: function () {
            alert('Failed to add to favourites.');
          }
        });
      }
      let favDataTable = null;

      function initFavouritesTable() {
        favDataTable = $('#favTable').DataTable({
          ajax: {
            url: '/Controller/JokeGenerator.php',
            type: 'GET',
            data: { action: 'fetch_favourites' }
          },
          columns: [
            { data: 'id' },
            { data: 'category' },
            { data: 'joke_text' },
            { data: 'date_created' },
            {
              data: null,
              render: function (data, type, row) {
                return `<i class="bi bi-star-fill text-primary favourite-star" data-id="${row.id}" style="cursor: pointer; font-size: 1.5rem;"></i>`;
              }
            }
          ],
          order: [[0, 'desc']]
        });

        $('#favTable').on('click', '.favourite-star', function () {
          const $star = $(this);
          const jokeId = $star.data('id');

          $.ajax({
            url: '/Controller/JokeGenerator.php',
            type: 'POST',
            data: {
              action: 'addToFavourites',
              joke_id: jokeId,
              status: 0 
            },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                favDataTable
                  .row($star.closest('tr'))
                  .remove()
                  .draw();
              } else {
                alert('Failed to remove from favourites.');
              }
            },
            error: function () {
              alert('Failed to update favourite.');
            }
          });
        });
      }

      function initSavedTable() {
        dataTable = $('#savedTable').DataTable({
          ajax: {
            url: '/Controller/JokeGenerator.php',
            type: 'GET',
            data: { action: 'fetch_saved' }
          },
          columns: [
            { data: 'id' },
            { data: 'category' },
            { data: 'joke_text' },
            { data: 'date_created' },
            {
              data: null,
              render: function (data, type, row) {
                return `<i class="bi bi-trash-fill text-danger delete-joke" data-id="${row.id}" style="cursor: pointer; font-size: 1.5rem;"></i>`;
              }
            }
          ],
          order: [[0, 'desc']]
        });
      }

      $('body').on('click', '.delete-joke', function () {
        let $trash = $(this);
        let jokeId = $trash.data('id');
        if (!confirm('Are you sure you want to delete this joke?')) return;

        $.ajax({
          url: '/Controller/JokeGenerator.php',
          type: 'POST',
          data: {
            action: 'deleteJoke',
            joke_id: jokeId
          },
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              alert('Joke deleted successfully.');
              dataTable.ajax.reload(); 
            } else {
              alert(response.error || 'Failed to delete the joke.');
            }
          },
          error: function () {
            alert('Failed to delete the joke.');
          }
        });
      })

    });
  </script>

</body>
</html>

