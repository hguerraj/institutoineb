/*

Sakila for Oracle is a port of the Sakila example database available for MySQL, which was originally developed by Mike Hillyer of the MySQL AB documentation team.
This project is designed to help database administrators to decide which database to use for development of new products
The user can run the same SQL against different kind of databases and compare the performance

License: BSD
Copyright DB Software Laboratory
http://www.etl-tools.com

*/

--
-- Table structure for table actor
--
--DROP TABLE actor;

CREATE TABLE actor (
  actor_id INT NOT NULL ,
  first_name VARCHAR(45) NOT NULL,
  last_name VARCHAR(45) NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_actor PRIMARY KEY  (actor_id)
);

CREATE INDEX idx_actor_last_name ON actor(last_name);
-- /

  --DROP SEQUENCE actor_sequence;

CREATE SEQUENCE actor_sequence;
-- /



CREATE OR REPLACE TRIGGER actor_before_trigger
BEFORE INSERT ON actor FOR EACH ROW
BEGIN
  IF (:NEW.actor_id IS NULL) THEN
    SELECT actor_sequence.nextval INTO :NEW.actor_id
    FROM DUAL;
  END IF;
  :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER actor_before_update
BEFORE UPDATE ON actor FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/


--
-- Table structure for table country
--

CREATE TABLE country (
  country_id INT NOT NULL,
  country VARCHAR(50) NOT NULL,
  last_update DATE,
  CONSTRAINT pk_country PRIMARY KEY (country_id)
);

-- DROP SEQUENCE country_sequence;

CREATE SEQUENCE country_sequence;
-- /


CREATE OR REPLACE TRIGGER country_before_trigger
BEFORE INSERT ON country FOR EACH ROW
BEGIN
  IF (:NEW.country_id IS NULL) THEN
    SELECT country_sequence.nextval INTO :NEW.country_id
    FROM DUAL;
  END IF;
  :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER country_before_update
BEFORE UPDATE ON country FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/


--
-- Table structure for table city
--

CREATE TABLE city (
  city_id INT NOT NULL,
  city VARCHAR(50) NOT NULL,
  country_id INT NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_city PRIMARY KEY (city_id),
  CONSTRAINT fk_city_country FOREIGN KEY (country_id) REFERENCES country (country_id)
);

CREATE INDEX idx_fk_country_id ON city(country_id);
-- /

--  DROP SEQUENCE city_sequence;

CREATE SEQUENCE city_sequence;
-- /

CREATE OR REPLACE TRIGGER city_before_trigger
BEFORE INSERT ON city FOR EACH ROW
BEGIN
  IF (:NEW.city_id IS NULL) THEN
    SELECT city_sequence.nextval INTO :NEW.city_id
    FROM DUAL;
  END IF;
 :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER city_before_update
BEFORE UPDATE ON city FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/


--
-- Table structure for table address
--

CREATE TABLE address (
  address_id INT NOT NULL,
  address VARCHAR(50) NOT NULL,
  address2 VARCHAR(50) DEFAULT NULL,
  district VARCHAR(20) NOT NULL,
  city_id INT  NOT NULL,
  postal_code VARCHAR(10) DEFAULT NULL,
  phone VARCHAR(20) NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_address PRIMARY KEY (address_id)
);

CREATE INDEX idx_fk_city_id ON address(city_id);
-- /

ALTER TABLE address ADD  CONSTRAINT fk_address_city FOREIGN KEY (city_id) REFERENCES city (city_id);
-- /

  --DROP SEQUENCE city_sequence;

CREATE SEQUENCE address_sequence;
-- /

CREATE OR REPLACE TRIGGER address_before_trigger
BEFORE INSERT ON address FOR EACH ROW
BEGIN
  IF (:NEW.address_id IS NULL) THEN
    SELECT address_sequence.nextval INTO :NEW.address_id
    FROM DUAL;
  END IF;
 :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER address_before_update
BEFORE UPDATE ON address FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/

--
-- Table structure for table language
--

CREATE TABLE language (
  language_id INT NOT NULL ,
  name CHAR(20) NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_language PRIMARY KEY (language_id)
);

-- DROP SEQUENCE language_sequence;

CREATE SEQUENCE language_sequence;
-- /

CREATE OR REPLACE TRIGGER language_before_trigger
BEFORE INSERT ON language FOR EACH ROW
BEGIN
  IF (:NEW.language_id IS NULL) THEN
    SELECT language_sequence.nextval INTO :NEW.language_id
    FROM DUAL;
  END IF;
  :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER language_before_update
BEFORE UPDATE ON language FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/

--
-- Table structure for table category
--

CREATE TABLE category (
  category_id INT NOT NULL,
  name VARCHAR(25) NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_category PRIMARY KEY  (category_id)
);

-- DROP SEQUENCE category_sequence;

CREATE SEQUENCE category_sequence;
-- /

CREATE OR REPLACE TRIGGER category_before_trigger
BEFORE INSERT ON category FOR EACH ROW
BEGIN
  IF (:NEW.category_id IS NULL) THEN
    SELECT category_sequence.nextval INTO :NEW.category_id
    FROM DUAL;
  END IF;
  :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER category_before_update
BEFORE UPDATE ON category FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/

--
-- Table structure for table customer
--

CREATE TABLE customer (
  customer_id INT NOT NULL,
  store_id INT NOT NULL,
  first_name VARCHAR(45) NOT NULL,
  last_name VARCHAR(45) NOT NULL,
  email VARCHAR(50) DEFAULT NULL,
  address_id INT NOT NULL,
  active CHAR(1) DEFAULT 'Y' NOT NULL,
  create_date DATE NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_customer PRIMARY KEY  (customer_id),
  CONSTRAINT fk_customer_address FOREIGN KEY (address_id) REFERENCES address(address_id)
);

CREATE INDEX idx_customer_fk_store_id ON customer(store_id);
-- /
CREATE INDEX idx_customer_fk_address_id ON customer(address_id);
-- /
CREATE INDEX idx_customer_last_name ON customer(last_name);
-- /
-- DROP SEQUENCE customer_sequence;

CREATE SEQUENCE customer_sequence;
-- /

CREATE OR REPLACE TRIGGER customer_before_trigger
BEFORE INSERT ON customer FOR EACH ROW
BEGIN
  IF (:NEW.customer_id IS NULL) THEN
    SELECT customer_sequence.nextval INTO :NEW.customer_id
    FROM DUAL;
  END IF;
  :NEW.last_update:=current_date;
  :NEW.create_date:=current_date;
END;
/
CREATE OR REPLACE TRIGGER customer_before_update
BEFORE UPDATE ON customer FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/
--
-- Table structure for table film
--

CREATE TABLE film (
  film_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description CLOB DEFAULT NULL,
  release_year VARCHAR(4) DEFAULT NULL,
  language_id INT NOT NULL,
  original_language_id INT DEFAULT NULL,
  rental_duration SMALLINT  DEFAULT 3 NOT NULL,
  rental_rate DECIMAL(4,2) DEFAULT 4.99 NOT NULL,
  length SMALLINT DEFAULT NULL,
  replacement_cost DECIMAL(5,2) DEFAULT 19.99 NOT NULL,
  rating VARCHAR(10) DEFAULT 'G',
  special_features VARCHAR(100) DEFAULT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_film PRIMARY KEY  (film_id),
  CONSTRAINT fk_film_language FOREIGN KEY (language_id) REFERENCES language (language_id) ,
  CONSTRAINT fk_film_language_original FOREIGN KEY (original_language_id) REFERENCES language (language_id)
);

ALTER TABLE film ADD CONSTRAINT CHECK_special_features CHECK(special_features is null or
                                                              special_features like '%Trailers%' or
                                                              special_features like '%Commentaries%' or
                                                              special_features like '%Deleted Scenes%' or
                                                              special_features like '%Behind the Scenes%');
-- /
ALTER TABLE film ADD CONSTRAINT CHECK_special_rating CHECK(rating in ('G','PG','PG-13','R','NC-17'));
-- /
CREATE INDEX idx_fk_language_id ON film(language_id);
-- /
CREATE INDEX idx_fk_original_language_id ON film(original_language_id);
-- /

-- DROP SEQUENCE film_sequence;

CREATE SEQUENCE film_sequence;
-- /

CREATE OR REPLACE TRIGGER film_before_trigger
BEFORE INSERT ON film FOR EACH ROW
BEGIN
 IF (:NEW.film_id IS NULL) THEN
   SELECT film_sequence.nextval INTO :NEW.film_id
    FROM DUAL;
  END IF;
  :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER film_before_update
BEFORE UPDATE ON film FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/

--
-- Table structure for table film_actor
--

CREATE TABLE film_actor (
  actor_id INT NOT NULL,
  film_id  INT NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_film_actor PRIMARY KEY  (actor_id,film_id),
  CONSTRAINT fk_film_actor_actor FOREIGN KEY (actor_id) REFERENCES actor (actor_id),
  CONSTRAINT fk_film_actor_film FOREIGN KEY (film_id) REFERENCES film (film_id)
);

CREATE INDEX idx_fk_film_actor_film ON film_actor(film_id);
-- /

CREATE INDEX idx_fk_film_actor_actor ON film_actor(actor_id) ;
-- /

CREATE OR REPLACE TRIGGER film_actor_before_trigger
BEFORE INSERT ON film_actor FOR EACH ROW
BEGIN
    :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER film_actor_before_update
BEFORE UPDATE ON film_actor FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/

--
-- Table structure for table film_category
--

CREATE TABLE film_category (
  film_id INT NOT NULL,
  category_id INT  NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_film_category PRIMARY KEY (film_id, category_id),
  CONSTRAINT fk_film_category_film FOREIGN KEY (film_id) REFERENCES film (film_id),
  CONSTRAINT fk_film_category_category FOREIGN KEY (category_id) REFERENCES category (category_id)
);

CREATE INDEX idx_fk_film_category_film ON film_category(film_id);
-- /
CREATE INDEX idx_fk_film_category_category ON film_category(category_id);
-- /

CREATE OR REPLACE TRIGGER film_category_before_trigger
BEFORE INSERT ON film_category FOR EACH ROW
BEGIN
    :NEW.last_update:=current_date;
END;
/

CREATE OR REPLACE TRIGGER film_category_before_update
BEFORE UPDATE ON film_category FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/
--
-- Table structure for table film_text
--

CREATE TABLE film_text (
  film_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description CLOB,
  CONSTRAINT pk_film_text PRIMARY KEY  (film_id)
);

--
-- Table structure for table inventory
--

CREATE TABLE inventory (
  inventory_id INT NOT NULL,
  film_id INT NOT NULL,
  store_id INT NOT NULL,
  last_update DATE NOT NULL,
  CONSTRAINT pk_inventory PRIMARY KEY  (inventory_id),
  CONSTRAINT fk_inventory_film FOREIGN KEY (film_id) REFERENCES film (film_id)
);

CREATE INDEX idx_fk_film_id ON inventory(film_id);
-- /

CREATE INDEX idx_fk_film_id_store_id ON inventory(store_id,film_id);
-- /

-- DROP SEQUENCE inventory_sequence;

CREATE SEQUENCE inventory_sequence;
-- /

CREATE OR REPLACE TRIGGER inventory_before_trigger
BEFORE INSERT ON inventory FOR EACH ROW
BEGIN
 IF (:NEW.inventory_id IS NULL) THEN
   SELECT inventory_sequence.nextval INTO :NEW.inventory_id
    FROM DUAL;
  END IF;
  :NEW.last_update:=current_date;
END;
/
CREATE OR REPLACE TRIGGER inventory_before_update
BEFORE UPDATE ON inventory FOR EACH ROW
BEGIN
  :NEW.last_update:=current_date;
END;
/
