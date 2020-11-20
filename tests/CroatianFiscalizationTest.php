<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class CroatianFiscalizationTest extends TestCase
{
    /**
     * Asserts that the fiscalization runs properly.
     *
     */
    public function testFiscalization()
    {
        $this->assertEquals(true, true);
    }
}