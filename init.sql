CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE jokes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  joke_text TEXT NOT NULL,
  category_id INT,
  date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE user_favourites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  joke_id INT NOT NULL,
  date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id, joke_id),
  FOREIGN KEY (joke_id) REFERENCES jokes(id)
);