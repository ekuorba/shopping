-- sample categories
INSERT INTO categories (name, slug, description) VALUES ('Electronics', 'electronics', 'Gadgets and electronics');
INSERT INTO categories (name, slug, description) VALUES ('Books', 'books', 'Printed and digital books');
INSERT INTO categories (name, slug, description) VALUES ('Apparel', 'apparel', 'Clothing and accessories');

-- sample products
INSERT INTO products (name, slug, description, price, stock, category_id, image) VALUES
('Wireless Headphones', 'wireless-headphones', 'Comfortable wireless headphones with long battery life', 59.99, 100, 1, 'img/headphones.jpg'),
('Smartphone Stand', 'smartphone-stand', 'Adjustable smartphone stand for desks', 12.50, 250, 1, 'img/stand.jpg'),
('Learning PHP', 'learning-php', 'A beginner-friendly book on PHP development', 19.99, 80, 2, 'img/book_php.jpg'),
('Cotton T-Shirt', 'cotton-tshirt', 'Soft cotton t-shirt available in multiple sizes', 14.99, 200, 3, 'img/tshirt.jpg');

-- create a minimal product_images entries
INSERT INTO product_images (product_id, url, is_primary) VALUES (1, 'img/headphones.jpg', 1);
INSERT INTO product_images (product_id, url, is_primary) VALUES (2, 'img/stand.jpg', 1);
INSERT INTO product_images (product_id, url, is_primary) VALUES (3, 'img/book_php.jpg', 1);
INSERT INTO product_images (product_id, url, is_primary) VALUES (4, 'img/tshirt.jpg', 1);
