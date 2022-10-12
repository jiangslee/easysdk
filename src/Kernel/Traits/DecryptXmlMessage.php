<?php

namespace EasySdk\Kernel\Traits;

use EasySdk\Kernel\Encryptor;
use EasySdk\Kernel\Exceptions\BadRequestException;
use EasySdk\Kernel\Message;
use EasySdk\Kernel\Support\Xml;

trait DecryptXmlMessage
{
    /**
     * @throws \EasySdk\Kernel\Exceptions\RuntimeException
     * @throws BadRequestException
     */
    public function decryptMessage(
        Message $message,
        Encryptor $encryptor,
        string $signature,
        int|string $timestamp,
        string $nonce
    ): Message {
        $ciphertext = $message->Encrypt;

        $this->validateSignature($encryptor->getToken(), $ciphertext, $signature, $timestamp, $nonce);

        $message->merge(Xml::parse(
            $encryptor->decrypt(
                ciphertext: $ciphertext,
                msgSignature: $signature,
                nonce: $nonce,
                timestamp: $timestamp
            )
        ) ?? []);

        return $message;
    }

    /**
     * @throws BadRequestException
     */
    protected function validateSignature(
        string $token,
        string $ciphertext,
        string $signature,
        int|string $timestamp,
        string $nonce
    ): void {
        if (empty($signature)) {
            throw new BadRequestException('Request signature must not be empty.');
        }

        $params = [$token, $timestamp, $nonce, $ciphertext];

        sort($params, SORT_STRING);

        if ($signature !== sha1(implode($params))) {
            throw new BadRequestException('Invalid request signature.');
        }
    }
}
