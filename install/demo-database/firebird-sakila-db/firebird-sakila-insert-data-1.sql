ALTER TABLE staff DROP CONSTRAINT fk_staff_address;
ALTER TABLE staff DROP CONSTRAINT fk_staff_store;
ALTER TABLE store DROP CONSTRAINT fk_store_staff;