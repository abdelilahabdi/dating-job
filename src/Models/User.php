<?php

namespace Src\Models;
use Src\Core\BaseModel;
use Src\Core\Validator;
use Src\Core\Security;

class User extends BaseModel
{
    protected $table = 'users';
    protected ?int $id = null;
    protected string $name;
    protected string $email;
    protected string $password;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getEmail(): string
    {
        return $this->email;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setPassword(string $plainPassword): bool
    {
        if (!Validator::validatePassword($plainPassword)) {
            return false;
        }

        $this->password = Security::hashPassword($plainPassword);
        return true;
    }

    public function validate(): bool
    {
        if (!Validator::validateEmail($this->email)) {
            return false;
        }
        return true;
    }
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        if ($this->id !== null) {
            return false;
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password
        ];

        $id = $this->create($data);

        if ($id) {
            $this->id = (int) $id;
            return true;
        }

        return false;
    }

}
