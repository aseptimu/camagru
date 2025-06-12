ALTER TABLE images
    ADD COLUMN user_id INT NULL;

UPDATE images
SET user_id = 1
WHERE user_id IS NULL;

ALTER TABLE images
    ALTER COLUMN user_id SET NOT NULL;

ALTER TABLE images
    ADD CONSTRAINT fk_images_user
        FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE;