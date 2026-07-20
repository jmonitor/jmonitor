<?php

declare(strict_types=1);

namespace App\EventListener\Stripe;

use App\Bridge\Stripe\StripeEventType;
use App\Entity\Enums\Plan;
use App\Entity\Order;
use App\Entity\Project;
use App\Entity\User;
use App\Event\StripeEvent;
use App\Order\OrderNumberGenerator;
use App\Order\OrderStatus;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Creates the order once it has been paid
 */
#[AsEventListener]
readonly class CreateOrderListener
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private OrderNumberGenerator $orderNumberGenerator,
    ) {}

    public function __invoke(StripeEvent $e): void
    {
        if ($e->type !== StripeEventType::CHECKOUT_SESSION_COMPLETED) {
            return;
        }

        $session = $e->stripeEvent->data->object;
        assert($session instanceof Session);

        // not sure this can happen, but just in case
        if ($session['status'] !== 'complete') {
            $this->logger->warning('stripe webhook status is not complete', [
                'session' => $session,
            ]);

            return;
        }

        if (!isset($session->metadata['project_id'])) {
            $this->logger->warning('project_id is missing', [
                'session' => $session,
            ]);

            return;
        }

        if (!isset($session->metadata['plan'])) {
            $this->logger->warning('plan is missing', [
                'session' => $session,
            ]);

            return;
        }

        $this->createOrder($session);
    }

    private function createOrder(Session $session): void
    {
        $user = $this->getUser($session);

        $order = new Order();
        $order->setPaidAt(new \DateTimeImmutable());
        $order->setUser($user);
        $order->setTotal($session->amount_total);
        $order->setProject($this->em->getReference(Project::class, $session->metadata['project_id']));
        $order->setStripeCustomerId($session->customer);
        $order->setPlan(Plan::from($session->metadata['plan']));
        $order->setNumber($this->orderNumberGenerator->generate());
        $order->setStatus(OrderStatus::PAID);

        $this->em->persist($order);
        $this->em->flush();
    }

    private function getUser(Session $session): ?User
    {
        $id = $session->client_reference_id;

        return $id ? $this->userRepository->find($id) : null;
    }
}
