<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Tests for \Magento\Framework\Data\Form\Element\Password
 */
namespace Magento\Framework\Data\Test\Unit\Form\Element;

class PasswordTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_objectManagerMock;

    /**
     * @var \Magento\Framework\Data\Form\Element\Password
     */
    protected $_model;

    protected function setUp()
    {
        $factoryMock = $this->getMock('\Magento\Framework\Data\Form\Element\Factory', [], [], '', false);
        $collectionFactoryMock = $this->getMock(
            '\Magento\Framework\Data\Form\Element\CollectionFactory',
            [],
            [],
            '',
            false
        );
        $escaperMock = $this->getMock('\Magento\Framework\Escaper', [], [], '', false);
        $this->_model = new \Magento\Framework\Data\Form\Element\Obscure(
            $factoryMock,
            $collectionFactoryMock,
            $escaperMock
        );
        $formMock = new \Magento\Framework\DataObject();
        $formMock->getHtmlIdPrefix('id_prefix');
        $formMock->getHtmlIdPrefix('id_suffix');
        $this->_model->setForm($formMock);
    }

    /**
     * @covers \Magento\Framework\Data\Form\Element\Password::__construct
     */
    public function testConstruct()
    {
        $this->assertEquals('password', $this->_model->getType());
        $this->assertEquals('textfield', $this->_model->getExtType());
    }

    /**
     * @covers \Magento\Framework\Data\Form\Element\Password::getHtml
     */
    public function testGetHtml()
    {
        $html = $this->_model->getHtml();
        $this->assertContains('type="password"', $html);
        $this->assertTrue(preg_match('/class=\".*input-text.*\"/i', $html) > 0);
    }
}
