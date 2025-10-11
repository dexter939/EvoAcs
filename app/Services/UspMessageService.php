<?php

namespace App\Services;

use Usp\Msg;
use Usp\Header;
use Usp\Body;
use Usp\Request;
use Usp\Response;
use Usp\Get;
use Usp\GetResp;
use Usp\Set;
use Usp\SetResp;
use Usp\Add;
use Usp\AddResp;
use Usp\Delete;
use Usp\DeleteResp;
use Usp\Operate;
use Usp\OperateResp;
use Usp_record\Record;
use Usp_record\NoSessionContextRecord;

/**
 * USP Message Service
 * 
 * Gestisce encoding/decoding dei messaggi TR-369 USP Protocol Buffers
 * Handles encoding/decoding of TR-369 USP Protocol Buffers messages
 */
class UspMessageService
{
    /**
     * Genera un Message ID univoco
     * Generate unique Message ID
     */
    protected function generateMessageId(): string
    {
        return 'msg-' . uniqid() . '-' . bin2hex(random_bytes(4));
    }

    /**
     * Crea un messaggio USP Get
     * Create USP Get message
     * 
     * @param array $paramPaths Array di parameter paths da recuperare / Array of parameter paths to retrieve
     * @param string|null $msgId Message ID personalizzato / Custom message ID
     * @return Msg
     */
    public function createGetMessage(array $paramPaths, ?string $msgId = null): Msg
    {
        $msgId = $msgId ?? $this->generateMessageId();

        // Create header
        $header = new Header();
        $header->setMsgId($msgId);
        $header->setMsgType(Header\MsgType::GET);

        // Create Get request
        $get = new Get();
        $get->setParamPaths($paramPaths);

        // Create request body
        $request = new Request();
        $request->setGet($get);

        $body = new Body();
        $body->setRequest($request);

        // Create complete message
        $msg = new Msg();
        $msg->setHeader($header);
        $msg->setBody($body);

        return $msg;
    }

    /**
     * Crea un messaggio USP Set
     * Create USP Set message
     * 
     * @param array $updateObjects Array di oggetti da aggiornare con parametri
     * @param bool $allowPartial Permetti aggiornamento parziale
     * @param string|null $msgId Message ID personalizzato
     * @return Msg
     */
    public function createSetMessage(array $updateObjects, bool $allowPartial = false, ?string $msgId = null): Msg
    {
        $msgId = $msgId ?? $this->generateMessageId();

        $header = new Header();
        $header->setMsgId($msgId);
        $header->setMsgType(Header\MsgType::SET);

        $set = new Set();
        $set->setAllowPartial($allowPartial);

        $updateObjs = [];
        foreach ($updateObjects as $objPath => $params) {
            $updateObj = new Set\UpdateObject();
            $updateObj->setObjPath($objPath);

            $paramSettings = [];
            foreach ($params as $paramName => $paramValue) {
                $paramSetting = new Set\UpdateParamSetting();
                $paramSetting->setParam($paramName);
                $paramSetting->setValue((string) $paramValue);
                $paramSetting->setRequired(true);

                $paramSettings[] = $paramSetting;
            }
            $updateObj->setParamSettings($paramSettings);
            $updateObjs[] = $updateObj;
        }
        $set->setUpdateObjs($updateObjs);

        $request = new Request();
        $request->setSet($set);

        $body = new Body();
        $body->setRequest($request);

        $msg = new Msg();
        $msg->setHeader($header);
        $msg->setBody($body);

        return $msg;
    }

    /**
     * Crea un messaggio USP Add
     * Create USP Add message
     * 
     * @param string $objPath Object path da creare
     * @param array $params Parametri dell'oggetto
     * @param bool $allowPartial Permetti creazione parziale
     * @param string|null $msgId Message ID personalizzato
     * @return Msg
     */
    public function createAddMessage(string $objPath, array $params = [], bool $allowPartial = false, ?string $msgId = null): Msg
    {
        $msgId = $msgId ?? $this->generateMessageId();

        $header = new Header();
        $header->setMsgId($msgId);
        $header->setMsgType(Header\MsgType::ADD);

        $add = new Add();
        $add->setAllowPartial($allowPartial);

        $createObj = new Add\CreateObject();
        $createObj->setObjPath($objPath);

        $paramSettings = [];
        foreach ($params as $paramName => $paramValue) {
            $paramSetting = new Add\CreateParamSetting();
            $paramSetting->setParam($paramName);
            $paramSetting->setValue((string) $paramValue);
            $paramSetting->setRequired(true);

            $paramSettings[] = $paramSetting;
        }
        $createObj->setParamSettings($paramSettings);
        
        $add->setCreateObjs([$createObj]);

        $request = new Request();
        $request->setAdd($add);

        $body = new Body();
        $body->setRequest($request);

        $msg = new Msg();
        $msg->setHeader($header);
        $msg->setBody($body);

        return $msg;
    }

    /**
     * Crea un messaggio USP Delete
     * Create USP Delete message
     * 
     * @param array $objPaths Array di object paths da eliminare
     * @param bool $allowPartial Permetti eliminazione parziale
     * @param string|null $msgId Message ID personalizzato
     * @return Msg
     */
    public function createDeleteMessage(array $objPaths, bool $allowPartial = false, ?string $msgId = null): Msg
    {
        $msgId = $msgId ?? $this->generateMessageId();

        $header = new Header();
        $header->setMsgId($msgId);
        $header->setMsgType(Header\MsgType::DELETE);

        $delete = new Delete();
        $delete->setAllowPartial($allowPartial);
        $delete->setObjPaths($objPaths);

        $request = new Request();
        $request->setDelete($delete);

        $body = new Body();
        $body->setRequest($request);

        $msg = new Msg();
        $msg->setHeader($header);
        $msg->setBody($body);

        return $msg;
    }

    /**
     * Crea un messaggio USP Operate
     * Create USP Operate message
     * 
     * @param string $command Comando da eseguire (es: Device.Reboot())
     * @param array $inputArgs Argomenti del comando
     * @param string|null $msgId Message ID personalizzato
     * @return Msg
     */
    public function createOperateMessage(string $command, array $inputArgs = [], ?string $msgId = null): Msg
    {
        $msgId = $msgId ?? $this->generateMessageId();

        $header = new Header();
        $header->setMsgId($msgId);
        $header->setMsgType(Header\MsgType::OPERATE);

        $operate = new Operate();
        $operate->setCommand($command);
        $operate->setSendResp(true);

        $inputArgsArray = [];
        foreach ($inputArgs as $argName => $argValue) {
            $operateArg = new Operate\OperateArg();
            $operateArg->setName($argName);
            $operateArg->setValue((string) $argValue);

            $inputArgsArray[] = $operateArg;
        }
        $operate->setInputArgs($inputArgsArray);

        $request = new Request();
        $request->setOperate($operate);

        $body = new Body();
        $body->setRequest($request);

        $msg = new Msg();
        $msg->setHeader($header);
        $msg->setBody($body);

        return $msg;
    }

    /**
     * Serializza un messaggio USP in formato binario
     * Serialize USP message to binary format
     * 
     * @param Msg $msg
     * @return string Binary protobuf data
     */
    public function serializeMessage(Msg $msg): string
    {
        return $msg->serializeToString();
    }

    /**
     * Deserializza un messaggio USP da formato binario
     * Deserialize USP message from binary format
     * 
     * @param string $binaryData
     * @return Msg
     */
    public function deserializeMessage(string $binaryData): Msg
    {
        $msg = new Msg();
        $msg->mergeFromString($binaryData);
        return $msg;
    }

    /**
     * Wrappa un messaggio USP in un USP Record per il transport
     * Wrap USP message in USP Record for transport
     * 
     * @param Msg $msg Messaggio USP
     * @param string $toId Endpoint ID destinazione
     * @param string $fromId Endpoint ID sorgente
     * @param string $version Versione USP (default: "1.3")
     * @return Record
     */
    public function wrapInRecord(Msg $msg, string $toId, string $fromId, string $version = '1.3'): Record
    {
        $record = new Record();
        $record->setVersion($version);
        $record->setToId($toId);
        $record->setFromId($fromId);

        $noSessionContext = new NoSessionContextRecord();
        $noSessionContext->setPayload($this->serializeMessage($msg));

        $record->setNoSessionContext($noSessionContext);

        return $record;
    }

    /**
     * Serializza un USP Record in formato binario
     * Serialize USP Record to binary format
     * 
     * @param Record $record
     * @return string Binary protobuf data
     */
    public function serializeRecord(Record $record): string
    {
        return $record->serializeToString();
    }

    /**
     * Deserializza un USP Record da formato binario
     * Deserialize USP Record from binary format
     * 
     * @param string $binaryData
     * @return Record
     */
    public function deserializeRecord(string $binaryData): Record
    {
        $record = new Record();
        $record->mergeFromString($binaryData);
        return $record;
    }

    /**
     * Estrae il messaggio USP da un Record
     * Extract USP message from Record
     * 
     * @param Record $record
     * @return Msg|null
     */
    public function extractMessageFromRecord(Record $record): ?Msg
    {
        if ($record->hasNoSessionContext()) {
            $payload = $record->getNoSessionContext()->getPayload();
            return $this->deserializeMessage($payload);
        }

        if ($record->hasSessionContext()) {
            $payload = $record->getSessionContext()->getPayload();
            return $this->deserializeMessage($payload);
        }

        return null;
    }

    /**
     * Verifica il tipo di messaggio
     * Check message type
     * 
     * @param Msg $msg
     * @return string|null Tipo messaggio (GET, SET, ADD, DELETE, OPERATE, ecc.)
     */
    public function getMessageType(Msg $msg): ?string
    {
        if (!$msg->hasHeader()) {
            return null;
        }

        $msgType = $msg->getHeader()->getMsgType();
        
        return match($msgType) {
            Header\MsgType::GET => 'GET',
            Header\MsgType::GET_RESP => 'GET_RESP',
            Header\MsgType::SET => 'SET',
            Header\MsgType::SET_RESP => 'SET_RESP',
            Header\MsgType::ADD => 'ADD',
            Header\MsgType::ADD_RESP => 'ADD_RESP',
            Header\MsgType::DELETE => 'DELETE',
            Header\MsgType::DELETE_RESP => 'DELETE_RESP',
            Header\MsgType::OPERATE => 'OPERATE',
            Header\MsgType::OPERATE_RESP => 'OPERATE_RESP',
            Header\MsgType::NOTIFY => 'NOTIFY',
            Header\MsgType::NOTIFY_RESP => 'NOTIFY_RESP',
            Header\MsgType::ERROR => 'ERROR',
            default => 'UNKNOWN'
        };
    }

    /**
     * Crea un messaggio di risposta Get completo da un array di risultati
     * Create complete Get response message from results array
     * 
     * @param string $msgId Message ID della request originale
     * @param array $results Array di risultati [path => value]
     * @return Msg
     */
    public function createGetResponseMessage(string $msgId, array $results): Msg
    {
        $header = new Header();
        $header->setMsgId($msgId);
        $header->setMsgType(Header\MsgType::GET_RESP);

        $getResp = new GetResp();

        $pathResults = [];
        foreach ($results as $path => $value) {
            $pathResult = new GetResp\RequestedPathResult();
            $pathResult->setRequestedPath($path);
            $pathResult->setErrCode(0); // Success

            $resolvedResult = new GetResp\ResolvedPathResult();
            $resolvedResult->setResolvedPath($path);
            // result_params is a map<string, string>
            $resolvedResult->setResultParams([$path => (string) $value]);

            $pathResult->setResolvedPathResults([$resolvedResult]);

            $pathResults[] = $pathResult;
        }
        $getResp->setReqPathResults($pathResults);

        $response = new Response();
        $response->setGetResp($getResp);

        $body = new Body();
        $body->setResponse($response);

        $msg = new Msg();
        $msg->setHeader($header);
        $msg->setBody($body);

        return $msg;
    }
}
