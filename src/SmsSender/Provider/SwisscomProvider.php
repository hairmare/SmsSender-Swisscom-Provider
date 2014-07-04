<?php

namespace SmsSender\Provider;

use SmsSender\Exception\InvalidCredentialsException;
use SmsSender\Exception\InvalidArgumentException;
use SmsSender\Result\ResultInterface;

class SwisscomProvider extends AbstractProvider
{
    /**
     * @var string
     */
    const SEND_SMS_URL = 'https://api.swisscom.com/v1/messaging/sms/outbound/tel%s/requests';

    /**
     * {@inheritDoc}
     *
     * @param object $adapter   adapter
     * @param string $client_id            API client id (probably the api secret from developer.swisscom.com)
     * @param string $international_prefix international prefix
     *
     * @return SwisscomProvider
     */
    public function __construct($adapter, $client_id, $international_prefix = '+41')
    {
        parent::__construct($adapter);

        $this->client_id = $client_id;
        $this->international_prefix = $international_prefix;
    }

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        if (null == $this->client_id) {
            throw new InvalidCredentialsException('No API credentials provided');
        }
        if (empty($originator)) {
            throw new InvalidArgumentException('The originator parameter is required for this provider.');
        }
        $url = sprintf(
            self::SEND_SMS_URL,
            urlencode(
                $this->localNumberToInternational(
                    $originator,
                    $this->international_prefix
                )
            )
        );
        $data = array(
            'to' => $this->localNumberToInternational($recipient, $this->international_prefix),
            'text' => $body,
            'from' => $originator,
            'type' => $this->containsUnicode($body) ? 'unicode' : 'text',
        );
        return $this->executeQuery($url, $data, array(
            'recipient' => $recipient,
            'body' => $body,
            'originator' => $originator,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'swisscom';
    }

    /**
     * do the query
     */
    protected function executeQuery($url, array $data = array(), array $extra_result_data = array())
    {
        $headers = array(
            'client_id: '.$this->client_id,
            'Content-Type: application/json',
            'Accept: application/json'
        );
        $request = new \stdClass;
        $request->outboundSMSMessageRequest = new \stdClass;
        $request->outboundSMSMessageRequest->address = array( sprintf('tel:%s', $data['to']) );
        $request->outboundSMSMessageRequest->senderAddress = sprintf('tel:%s', $data['from']);
        $request->outboundSMSMessageRequest->outboundSMSTextMessage = new \stdClass;
        $request->outboundSMSMessageRequest->outboundSMSTextMessage->message = $data['text'];
                
        $content = $this->getAdapter()->getContent($url, 'POST', $headers, json_encode($request));

        if (null == $content) {
            $results = $this->getDefaults();
        }
        if (is_string($content)) {
                $content = json_decode($content, true);
            $results['id'] = $content['outboundSMSMessageRequest']['clientCorrelator'];
            switch ($content['outboundSMSMessageRequest']['deliveryInfoList']['deliveryInfo'][0]['deliveryStatus']) {
                case 'DeliveredToNetwork':
                    $results['status'] = ResultInterface::STATUS_SENT;
                    break;
                case 'DeliveryImpossible':
                    $results['status'] = ResultInterface::STATUS_FAILED;
                    break;
            }
        }

        return array_merge($results, $extra_result_data);
    }
}
