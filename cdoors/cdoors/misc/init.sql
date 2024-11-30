CREATE TABLE IF NOT EXISTS users (
    personID SERIAL PRIMARY KEY,
    Fname VARCHAR(100),
    Lname VARCHAR(100),
    dob DATE,
    username VARCHAR(50),
    email VARCHAR(100),
    password VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS vm_details (
    ownerID INT,
    user_login VARCHAR(255),
    vm_ssh_password VARCHAR(255),
    private_ip VARCHAR(255),
    FOREIGN KEY (ownerID) REFERENCES users(personID) ON DELETE CASCADE
);

CREATE OR REPLACE FUNCTION insert_user(
    p_Fname VARCHAR(100),
    p_Lname VARCHAR(100),
    p_dob DATE,
    p_username VARCHAR(50),
    p_email VARCHAR(100),
    p_password VARCHAR(255)
) RETURNS VOID AS $$
BEGIN
    INSERT INTO users (Fname, Lname, dob, username, email, password)
    VALUES (p_Fname, p_Lname, p_dob, p_username, p_email, p_password);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION select_user(p_username VARCHAR(50))
RETURNS TABLE(personID INT, Fname VARCHAR(100), Lname VARCHAR(100), dob DATE, username VARCHAR(50), email VARCHAR(100), password VARCHAR(255)) AS $$
BEGIN
    RETURN QUERY
    SELECT personID, Fname, Lname, dob, username, email, password
    FROM users
    WHERE username = p_username;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION insert_vm(
    p_ownerID INT,
    p_vm_ip VARCHAR(45),
    p_vm_port INT,
    p_vm_ssh_password VARCHAR(255)
) RETURNS VOID AS $$
BEGIN
    INSERT INTO vm_details (ownerID, vm_ip, vm_port, vm_ssh_password)
    VALUES (p_ownerID, p_vm_ip, p_vm_port, p_vm_ssh_password);
END;
$$ LANGUAGE plpgsql;