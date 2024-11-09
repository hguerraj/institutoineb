--
-- Table structure for table staff
--

CREATE TABLE staff (
  staff_id INT NOT NULL,
  first_name VARCHAR(45) NOT NULL,
  last_name VARCHAR(45) NOT NULL,
  address_id INT NOT NULL,
  picture BLOB DEFAULT NULL,
  email VARCHAR(50) DEFAULT NULL,
  store_id INT NOT NULL,
  active SMALLINT DEFAULT 1 NOT NULL,
  username VARCHAR(16) NOT NULL,
  password VARCHAR(40) DEFAULT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_staff PRIMARY KEY  (staff_id),
  CONSTRAINT fk_staff_address FOREIGN KEY (address_id) REFERENCES address (address_id)
);

CREATE INDEX idx_fk_staff_store_id ON staff(store_id);
-- /

CREATE INDEX idx_fk_staff_address_id ON staff(address_id);
-- /

-- DROP SEQUENCE inventory_sequence;

CREATE SEQUENCE staff_sequence;
-- /

CREATE OR REPLACE TRIGGER staff_before_trigger
BEFORE INSERT ON staff FOR EACH ROW
BEGIN
 IF (:NEW.staff_id IS NULL) THEN
   SELECT staff_sequence.nextval INTO :NEW.staff_id
    FROM DUAL;
  END IF;
  :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER staff_before_update
BEFORE UPDATE ON staff FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/

--
-- Table structure for table store
--

CREATE TABLE store (
  store_id INT NOT NULL,
  manager_staff_id INT NOT NULL,
  address_id INT NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_store PRIMARY KEY  (store_id),
  CONSTRAINT fk_store_staff FOREIGN KEY (manager_staff_id) REFERENCES staff (staff_id) ,
  CONSTRAINT fk_store_address FOREIGN KEY (address_id) REFERENCES address (address_id)
);

CREATE INDEX idx_store_fk_manager_staff_id ON store(manager_staff_id);
-- /

CREATE INDEX idx_fk_store_address ON store(address_id);
-- /

-- DROP SEQUENCE store_sequence;

CREATE SEQUENCE store_sequence;
-- /



CREATE OR REPLACE TRIGGER store_before_trigger
BEFORE INSERT ON store FOR EACH ROW
BEGIN
 IF (:NEW.store_id IS NULL) THEN
   SELECT store_sequence.nextval INTO :NEW.store_id
    FROM DUAL;
  END IF;
 :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER store_before_update
BEFORE UPDATE ON store FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/

--
-- Table structure for table payment
--

CREATE TABLE payment (
  payment_id INT NOT NULL,
  customer_id INT  NOT NULL,
  staff_id INT NOT NULL,
  rental_id INT DEFAULT NULL,
  amount DECIMAL(5,2) NOT NULL,
  payment_date DATE NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_payment PRIMARY KEY  (payment_id),
  CONSTRAINT fk_payment_customer FOREIGN KEY (customer_id) REFERENCES customer (customer_id) ,
  CONSTRAINT fk_payment_staff FOREIGN KEY (staff_id) REFERENCES staff (staff_id)
);

CREATE INDEX idx_fk_staff_id ON payment(staff_id);
-- /
CREATE INDEX idx_fk_customer_id ON payment(customer_id);
-- /

-- DROP SEQUENCE payment_sequence;

CREATE SEQUENCE payment_sequence;
-- /

CREATE OR REPLACE TRIGGER payment_before_trigger
BEFORE INSERT ON payment FOR EACH ROW
BEGIN
 IF (:NEW.payment_id IS NULL) THEN
   SELECT payment_sequence.nextval INTO :NEW.payment_id
    FROM DUAL;
  END IF;
 :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER payment_before_update
BEFORE UPDATE ON payment FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/

CREATE TABLE rental (
  rental_id INT NOT NULL,
  rental_date DATE NOT NULL,
  inventory_id INT  NOT NULL,
  customer_id INT  NOT NULL,
  return_date DATE DEFAULT NULL,
  staff_id INT  NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_rental PRIMARY KEY (rental_id),
  CONSTRAINT fk_rental_staff FOREIGN KEY (staff_id) REFERENCES staff (staff_id) ,
  CONSTRAINT fk_rental_inventory FOREIGN KEY (inventory_id) REFERENCES inventory (inventory_id) ,
  CONSTRAINT fk_rental_customer FOREIGN KEY (customer_id) REFERENCES customer (customer_id)
);

CREATE INDEX idx_rental_fk_inventory_id ON rental(inventory_id);
-- /
CREATE INDEX idx_rental_fk_customer_id ON rental(customer_id);
-- /
CREATE INDEX idx_rental_fk_staff_id ON rental(staff_id);
-- /
CREATE UNIQUE INDEX   idx_rental_uq  ON rental (rental_date,inventory_id,customer_id);
-- /

-- DROP SEQUENCE payment_sequence;

CREATE SEQUENCE rental_sequence;
-- /

CREATE OR REPLACE TRIGGER rental_before_trigger
BEFORE INSERT ON rental FOR EACH ROW
BEGIN
 IF (:NEW.rental_id IS NULL) THEN
   SELECT rental_sequence.nextval INTO :NEW.rental_id
    FROM DUAL;
  END IF;
 :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER rental_before_update
BEFORE UPDATE ON rental FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/

-- FK CONSTRAINTS
ALTER TABLE customer ADD CONSTRAINT fk_customer_store FOREIGN KEY (store_id) REFERENCES store (store_id);
-- /
ALTER TABLE inventory ADD CONSTRAINT fk_inventory_store FOREIGN KEY (store_id) REFERENCES store (store_id);
-- /
ALTER TABLE staff ADD CONSTRAINT fk_staff_store FOREIGN KEY (store_id) REFERENCES store (store_id);
-- /
ALTER TABLE payment ADD CONSTRAINT fk_payment_rental FOREIGN KEY (rental_id) REFERENCES rental (rental_id) ON DELETE SET NULL;
-- /
--
-- View structure for view customer_list
--

-- CREATE OR REPLACE VIEW customer_list
-- AS
-- SELECT cu.customer_id AS ID,
--        cu.first_name||' '||cu.last_name AS name,
--        a.address AS address,
--        a.postal_code AS zip_code,
--        a.phone AS phone,
--        city.city AS city,
--        country.country AS country,
--        decode(cu.active, 1,'active','') AS notes,
--        cu.store_id AS SID
-- FROM customer cu JOIN address a ON cu.address_id = a.address_id JOIN city ON a.city_id = city.city_id
--     JOIN country ON city.country_id = country.country_id;
-- /
--
-- View structure for view film_list
--

-- CREATE OR REPLACE VIEW film_list
-- AS
-- SELECT film.film_id AS FID,
--        film.title AS title,
--        film.description AS description,
--        category.name AS category,
--        film.rental_rate AS price,
--        film.length AS length,
--        film.rating AS rating,
--        actor.first_name||' '||actor.last_name AS actors
-- FROM category LEFT JOIN film_category ON category.category_id = film_category.category_id LEFT JOIN film ON film_category.film_id = -- film.film_id
--         JOIN film_actor ON film.film_id = film_actor.film_id
--     JOIN actor ON film_actor.actor_id = actor.actor_id;
-- /

--
-- View structure for view staff_list
--

-- CREATE OR REPLACE VIEW staff_list
-- AS
-- SELECT s.staff_id AS ID,
--        s.first_name||' '||s.last_name AS name,
--        a.address AS address,
--        a.postal_code AS zip_code,
--        a.phone AS phone,
--        city.city AS city,
--        country.country AS country,
--        s.store_id AS SID
-- FROM staff s JOIN address a ON s.address_id = a.address_id JOIN city ON a.city_id = city.city_id
--     JOIN country ON city.country_id = country.country_id;
-- /
--
-- View structure for view sales_by_store
--

-- CREATE OR REPLACE VIEW sales_by_store
-- AS
-- SELECT
--   s.store_id
--  ,c.city||','||cy.country AS store
--  ,m.first_name||' '||m.last_name AS manager
--  ,SUM(p.amount) AS total_sales
-- FROM payment p
-- INNER JOIN rental r ON p.rental_id = r.rental_id
-- INNER JOIN inventory i ON r.inventory_id = i.inventory_id
-- INNER JOIN store s ON i.store_id = s.store_id
-- INNER JOIN address a ON s.address_id = a.address_id
-- INNER JOIN city c ON a.city_id = c.city_id
-- INNER JOIN country cy ON c.country_id = cy.country_id
-- INNER JOIN staff m ON s.manager_staff_id = m.staff_id
-- GROUP BY
--   s.store_id
-- , c.city||','||cy.country
-- , m.first_name||' '||m.last_name;
-- /
--
-- View structure for view sales_by_film_category
--
-- Note that total sales will add up to >100% because
-- some titles belong to more than 1 category
--

-- CREATE OR REPLACE VIEW sales_by_film_category
-- AS
-- SELECT
-- c.name AS category
-- , SUM(p.amount) AS total_sales
-- FROM payment p
-- INNER JOIN rental r ON p.rental_id = r.rental_id
-- INNER JOIN inventory i ON r.inventory_id = i.inventory_id
-- INNER JOIN film f ON i.film_id = f.film_id
-- INNER JOIN film_category fc ON f.film_id = fc.film_id
-- INNER JOIN category c ON fc.category_id = c.category_id
-- GROUP BY c.name;
-- /

--
-- View structure for view actor_info
--

-- CREATE VIEW actor_info
-- AS
-- SELECT
-- a.actor_id,
-- a.first_name,
-- a.last_name,
-- GROUP_CONCAT(DISTINCT CONCAT(c.name, ': ',
--         (SELECT GROUP_CONCAT(f.title ORDER BY f.title SEPARATOR ', ')
--                     FROM sakila.film f
--                     INNER JOIN sakila.film_category fc
--                       ON f.film_id = fc.film_id
--                     INNER JOIN sakila.film_actor fa
--                       ON f.film_id = fa.film_id
--                     WHERE fc.category_id = c.category_id
--                     AND fa.actor_id = a.actor_id
--                  )
--              )
--              ORDER BY c.name SEPARATOR '; ')
-- AS film_info
-- FROM sakila.actor a
-- LEFT JOIN sakila.film_actor fa
--   ON a.actor_id = fa.actor_id
-- LEFT JOIN sakila.film_category fc
--   ON fa.film_id = fc.film_id
-- LEFT JOIN sakila.category c
--   ON fc.category_id = c.category_id
-- GROUP BY a.actor_id, a.first_name, a.last_name;

-- TO DO PROCEDURES