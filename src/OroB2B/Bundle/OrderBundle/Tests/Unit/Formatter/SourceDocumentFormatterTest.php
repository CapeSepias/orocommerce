<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Formatter;

use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;

class SourceDocumentFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceDocumentFormatter
     */
    protected $sourceDocumentFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ChainEntityClassNameProvider
     */
    protected $chainEntityClassNameProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->chainEntityClassNameProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sourceDocumentFormatter = new SourceDocumentFormatter(
            $this->chainEntityClassNameProvider
        );
    }

    /**
     * @dataProvider getProvider
     *
     * @param $sourceDocumentClass
     * @param $sourceDocumentId
     * @param $sourceDocumentIdentifier
     * @param $expectedFormat
     * @param $expectUseGetEntityClassName
     */
    public function testFormat(
        $sourceDocumentClass,
        $sourceDocumentId,
        $sourceDocumentIdentifier,
        $expectedFormat,
        $expectUseGetEntityClassName
    ) {
        if ($expectUseGetEntityClassName) {
            $this->chainEntityClassNameProvider
                ->expects($this->once())
                ->method('getEntityClassName')
                ->willReturn($sourceDocumentClass);
        } else {
            $this->chainEntityClassNameProvider
                ->expects($this->never())
                ->method('getEntityClassName');
        }

        $response = $this->sourceDocumentFormatter->format(
            $sourceDocumentClass,
            $sourceDocumentId,
            $sourceDocumentIdentifier
        );

        self::assertEquals($expectedFormat, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider()
    {
        return [
            'empty class and empty identifier' => [
                'sourceDocumentClass' => null,
                'sourceDocumentId' => null,
                '$sourceDocumentIdentifier' => null,
                'expectedFormat' => '',
                'expectUseGetEntityClassName' => false
            ],
            'order without identifier' => [
                'sourceDocumentClass' => 'Order',
                'sourceDocumentId' => 1,
                '$sourceDocumentIdentifier' => null,
                'expectedFormat' => 'Order 1',
                'expectUseGetEntityClassName' => true
            ],
            'order with identifier' => [
                'sourceDocumentClass' => 'Order',
                'sourceDocumentId' => 1,
                '$sourceDocumentIdentifier' => 'FR1012401',
                'expectedFormat' => 'Order FR1012401',
                'expectUseGetEntityClassName' => true
            ],
            'order without identifier and id' => [
                'sourceDocumentClass' => 'Order',
                'sourceDocumentId' => null,
                '$sourceDocumentIdentifier' => null,
                'expectedFormat' => 'Order',
                'expectUseGetEntityClassName' => true
            ]
        ];
    }
}
