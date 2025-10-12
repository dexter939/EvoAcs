<?php

namespace Tests\Unit\Services;

use App\Services\UspMessageService;
use PHPUnit\Framework\TestCase;
use Usp\Msg;
use Usp\Header;

class UspMessageServiceTest extends TestCase
{
    private UspMessageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UspMessageService();
    }

    public function test_create_get_message_returns_valid_msg(): void
    {
        $paramPaths = [
            'Device.DeviceInfo.SoftwareVersion',
            'Device.WiFi.SSID.1.SSID'
        ];

        $msg = $this->service->createGetMessage($paramPaths);

        $this->assertInstanceOf(Msg::class, $msg);
        $this->assertInstanceOf(Header::class, $msg->getHeader());
        $this->assertEquals(Header\MsgType::GET, $msg->getHeader()->getMsgType());
        $this->assertNotEmpty($msg->getHeader()->getMsgId());
    }

    public function test_create_get_message_with_custom_msg_id(): void
    {
        $customMsgId = 'custom-test-id-12345';
        $paramPaths = ['Device.DeviceInfo.'];

        $msg = $this->service->createGetMessage($paramPaths, $customMsgId);

        $this->assertEquals($customMsgId, $msg->getHeader()->getMsgId());
    }

    public function test_create_set_message_returns_valid_msg(): void
    {
        $updateObjects = [
            'Device.ManagementServer.' => [
                'PeriodicInformInterval' => '600',
                'PeriodicInformEnable' => 'true'
            ]
        ];

        $msg = $this->service->createSetMessage($updateObjects);

        $this->assertInstanceOf(Msg::class, $msg);
        $this->assertEquals(Header\MsgType::SET, $msg->getHeader()->getMsgType());
        $this->assertTrue($msg->getBody()->hasRequest());
        $this->assertTrue($msg->getBody()->getRequest()->hasSet());
    }

    public function test_create_add_message_returns_valid_msg(): void
    {
        $objPath = 'Device.WiFi.SSID.';
        $params = [
            'SSID' => 'NewNetwork',
            'Enable' => 'true'
        ];

        $msg = $this->service->createAddMessage($objPath, $params);

        $this->assertInstanceOf(Msg::class, $msg);
        $this->assertEquals(Header\MsgType::ADD, $msg->getHeader()->getMsgType());
        $this->assertTrue($msg->getBody()->hasRequest());
        $this->assertTrue($msg->getBody()->getRequest()->hasAdd());
    }

    public function test_create_delete_message_returns_valid_msg(): void
    {
        $objPaths = [
            'Device.WiFi.SSID.3.',
            'Device.WiFi.SSID.4.'
        ];

        $msg = $this->service->createDeleteMessage($objPaths);

        $this->assertInstanceOf(Msg::class, $msg);
        $this->assertEquals(Header\MsgType::DELETE, $msg->getHeader()->getMsgType());
        $this->assertTrue($msg->getBody()->hasRequest());
        $this->assertTrue($msg->getBody()->getRequest()->hasDelete());
    }

    public function test_create_operate_message_returns_valid_msg(): void
    {
        $command = 'Device.Reboot()';
        $inputArgs = [];

        $msg = $this->service->createOperateMessage($command, $inputArgs);

        $this->assertInstanceOf(Msg::class, $msg);
        $this->assertEquals(Header\MsgType::OPERATE, $msg->getHeader()->getMsgType());
        $this->assertTrue($msg->getBody()->hasRequest());
        $this->assertTrue($msg->getBody()->getRequest()->hasOperate());
    }

    public function test_create_subscription_message_returns_valid_msg(): void
    {
        $subscriptionPath = 'Device.LocalAgent.Subscription.';
        $subscriptionParams = [
            'ID' => 'test-sub-1',
            'Enable' => 'true',
            'NotifType' => 'ValueChange'
        ];

        $msg = $this->service->createSubscriptionMessage($subscriptionPath, $subscriptionParams);

        $this->assertInstanceOf(Msg::class, $msg);
        $this->assertNotEmpty($msg->getHeader()->getMsgId());
    }

    public function test_serialize_and_deserialize_message(): void
    {
        $paramPaths = ['Device.DeviceInfo.'];
        $originalMsg = $this->service->createGetMessage($paramPaths, 'test-serialize-123');

        $serialized = $this->service->serializeMessage($originalMsg);
        
        $this->assertIsString($serialized);
        $this->assertNotEmpty($serialized);

        $deserialized = $this->service->deserializeMessage($serialized);
        
        $this->assertInstanceOf(Msg::class, $deserialized);
        $this->assertEquals('test-serialize-123', $deserialized->getHeader()->getMsgId());
        $this->assertEquals(Header\MsgType::GET, $deserialized->getHeader()->getMsgType());
    }

    public function test_wrap_in_record_creates_valid_record(): void
    {
        $msg = $this->service->createGetMessage(['Device.']);
        $toId = 'proto::controller-1';
        $fromId = 'proto::device-12345';

        $record = $this->service->wrapInRecord($msg, $toId, $fromId);

        $this->assertInstanceOf(\Usp_record\Record::class, $record);
        $this->assertEquals($toId, $record->getToId());
        $this->assertEquals($fromId, $record->getFromId());
        $this->assertEquals('1.3', $record->getVersion());
    }

    public function test_serialize_and_deserialize_record(): void
    {
        $msg = $this->service->createGetMessage(['Device.'], 'record-test-456');
        $record = $this->service->wrapInRecord($msg, 'proto::to', 'proto::from');

        $serialized = $this->service->serializeRecord($record);
        
        $this->assertIsString($serialized);
        $this->assertNotEmpty($serialized);

        $deserialized = $this->service->deserializeRecord($serialized);
        
        $this->assertEquals('proto::to', $deserialized->getToId());
        $this->assertEquals('proto::from', $deserialized->getFromId());
    }

    public function test_extract_message_from_record(): void
    {
        $originalMsg = $this->service->createGetMessage(['Device.'], 'extract-test-789');
        $record = $this->service->wrapInRecord($originalMsg, 'proto::to', 'proto::from');

        $extractedMsg = $this->service->extractMessageFromRecord($record);

        $this->assertInstanceOf(Msg::class, $extractedMsg);
        $this->assertEquals('extract-test-789', $extractedMsg->getHeader()->getMsgId());
    }

    public function test_get_message_type_identifies_request_types(): void
    {
        $getMsg = $this->service->createGetMessage(['Device.']);
        $this->assertEquals('GET', $this->service->getMessageType($getMsg));

        $setMsg = $this->service->createSetMessage(['Device.' => ['param' => 'value']]);
        $this->assertEquals('SET', $this->service->getMessageType($setMsg));

        $deleteMsg = $this->service->createDeleteMessage(['Device.Object.']);
        $this->assertEquals('DELETE', $this->service->getMessageType($deleteMsg));
    }

    public function test_create_get_response_message(): void
    {
        $msgId = 'response-test-001';
        $results = [
            ['path' => 'Device.DeviceInfo.SoftwareVersion', 'value' => 'v1.2.3']
        ];

        $responseMsg = $this->service->createGetResponseMessage($msgId, $results);

        $this->assertInstanceOf(Msg::class, $responseMsg);
        $this->assertEquals($msgId, $responseMsg->getHeader()->getMsgId());
        $this->assertEquals(Header\MsgType::GET_RESP, $responseMsg->getHeader()->getMsgType());
        $this->assertTrue($responseMsg->getBody()->hasResponse());
    }

    public function test_create_set_response_message(): void
    {
        $msgId = 'set-response-001';
        $updatedParams = [
            ['path' => 'Device.ManagementServer.PeriodicInformInterval']
        ];

        $responseMsg = $this->service->createSetResponseMessage($msgId, $updatedParams);

        $this->assertInstanceOf(Msg::class, $responseMsg);
        $this->assertEquals($msgId, $responseMsg->getHeader()->getMsgId());
        $this->assertEquals(Header\MsgType::SET_RESP, $responseMsg->getHeader()->getMsgType());
    }
}
