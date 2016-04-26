<?php
namespace Acme\PaypalAdaptivePaymentsBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Payum;
use Payum\Paypal\AdaptivePayments\Json\Api;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase_doctrine_orm",
     *   name="acme_paypal_adaptive_payments_prepare_simple_purchase_doctrine_orm"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareSimplePurchaseAndDoctrineOrmAction(Request $request)
    {
        $gatewayName = 'paypal_adaptive_payments';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['actionType'] = Api::PAY_ACTION_TYPE_PAY;
            $payment['currencyCode'] = $data['currency'];
            $payment['senderEmail'] = $data['sender'];
            $payment['receiverList'] = [
                'receiver' => [
                    [
                        'email' => $data['receiver'],
                        'amount' => $data['amount'],
                    ],
                ],
            ];
            $payment['local'] = [
                'device_ipaddress' => '127.0.0.1',
            ];
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('sender', 'email')
            ->add('receiver', 'email')
            ->add('amount', null, array(
                'data' => 1,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('currency', null, array('data' => 'USD'))
            ->getForm()
        ;
    }

    /**
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
