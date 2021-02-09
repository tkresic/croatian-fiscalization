<?php

namespace tonikresic\CroatianFiscalization\Company;

class Company
{
    /**
     * Company UID.
     *
     * @var string
     */
    public string $uid;

    /**
     * Company fiscalization Certificate.
     *
     * @var Certificate
     */
    public Certificate $certificate;

    /**
     * Demo fiscalization.
     *
     * @var bool
     */
    public bool $demo;


    /**
     * If the Invoice should be taxed.
     *
     * @var bool
     */
    public bool $shouldBeTaxed;

    /**
     * Company constructor.
     *
     * @param string $uid
     * @param Certificate $certificate
     * @param bool $demo
     * @param bool $shouldBeTaxed
     */
    public function __construct(string $uid, Certificate $certificate, bool $demo, bool $shouldBeTaxed)
    {
        $this->uid = $uid;
        $this->certificate = $certificate;
        $this->demo = $demo;
        $this->shouldBeTaxed = $shouldBeTaxed;
    }
}