<?php

namespace tonikresic\CroatianFiscalization\Company;

class Certificate
{
    /**
     * Path to the Certificate.
     *
     * @var string
     */
    public string $path;

    /**
     * Certificate password.
     *
     * @var string
     */
    public string $password;

    /**
     * Certificate constructor.
     *
     * @param string $path
     * @param string $password
     */
    public function __construct(string $path, string $password)
    {
        $this->path = $path;
        $this->password = $password;
    }
}