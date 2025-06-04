CREATE TABLE IF NOT EXISTS users (
     id SERIAL PRIMARY KEY,
     username VARCHAR(255) UNIQUE NOT NULL,
     email VARCHAR(255) UNIQUE NOT NULL,
     password_hash VARCHAR(255) NOT NULL,
     is_confirmed BOOLEAN DEFAULT FALSE,
     confirmation_token VARCHAR(255),
     created_at TIMESTAMPTZ DEFAULT NOW()
);




