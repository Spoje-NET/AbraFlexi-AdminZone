<?php

namespace Test\AdminZone;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use AdminZone\IPEX;

/**
 * Class IPEX.
 *
 * @covers \AdminZone\IPEX
 */
class IPEXTest extends TestCase
{
    /**
     * @var IPEX $iPEX An instance of "IPEX" to test.
     */
    private $object;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        /** @todo Maybe add some arguments to this constructor */
        $this->object = new IPEX();
    }

    /**
     * @covers \AdminZone\IPEX::getIpexInvoices
     */
    public function testGetIpexInvoices(): void
    {
        $this->assertArrayHasKey('0', $this->object->getIpexInvoices());
    }

    /**
     * @covers \AdminZone\IPEX::processIpexInvoices
     */
    public function testProcessIpexInvoices(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::getUnivoicedCalls
     */
    public function testGetUnivoicedCalls(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::getUsersPreparedOrders
     */
    public function testGetUsersPreparedOrders(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::foundLastInvoice
     */
    public function testFoundLastInvoice(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::uninvoicedAmount
     */
    public function testUninvoicedAmount(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::createOrder
     */
    public function testCreateOrder(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::pdfCallLog
     */
    public function testPdfCallLog(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::addCallLogAsItems
     */
    public function testAddCallLogAsItems(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::sendOrderByMail
     */
    public function testSendOrderByMail(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::noIpexExtID
     */
    public function testNoIpexExtID(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::createInvoice
     */
    public function testCreateInvoice(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \AdminZone\IPEX::getInvoicer
     */
    public function testGetInvoicer(): void
    {
        $this->assertIsObject($this->object->getInvoicer());
    }
}
