<?php

class User
{
  private function __construct(
    public int $id,
    public string $first_name,
    public string $last_name,
    public string $email,
    public string $password
) {}

public static function findOneByEmail(string $email): ?User
{
    $databaseConnection = new PDO(
        "mysql:host=mariadb;dbname=database",
        "user",
        "password"
    );

    $getUserQuery = $databaseConnection->prepare(
        "SELECT id, first_name, last_name, email, password FROM users WHERE email = :email"
    );

    $getUserQuery->execute(["email" => $email]);

    $user = $getUserQuery->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    return new User($user["id"], $user["first_name"], $user["last_name"], $user["email"], $user["password"]);
}
  public function isValidPassword(string $password): bool
  {
      return password_verify($password, $this->password);
  }
}
