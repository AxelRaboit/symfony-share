<?php

namespace App\Tests\Functional\Entity;

use App\Entity\Budget;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\TransactionCategory;
use App\Entity\User;
use App\Enum\TransactionTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TransactionTest extends KernelTestCase
{
    private const string USER_EMAIL = 'test@gmail.com';

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $doctrine = $kernel->getContainer()->get('doctrine');

        if (!$doctrine instanceof ManagerRegistry) {
            throw new \RuntimeException('Doctrine service is not of type ManagerRegistry');
        }

        $entityManager = $doctrine->getManager();

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Entity manager is not of type EntityManagerInterface');
        }

        $this->entityManager = $entityManager;
    }

    public function testTransactionType(): void
    {
        $budget = new Budget();
        $budget->setIndividual($this->getUser());
        $budget->setYear(2024);
        $budget->setMonth(6);
        $budget->setStartDate(new \DateTime('2024-06-01'));
        $budget->setEndDate(new \DateTime('2024-06-30'));
        $budget->setCurrency('EUR');
        $budget->setStartBalance(1000.00);
        $budget->setLeftToSpend(1000.00);

        $this->entityManager->persist($budget);

        $category = new Category();
        $category->setName('Test Category');

        $this->entityManager->persist($category);

        $this->entityManager->flush();

        $transactionCategory = new TransactionCategory();
        $transactionCategory->setName('Test Category');

        $this->entityManager->persist($transactionCategory);

        $this->entityManager->flush();

        $transaction = new Transaction();
        $transaction->setType(TransactionTypeEnum::INCOME());
        $transaction->setAmount(100.00);
        $transaction->setDate(new \DateTime('2024-06-01'));
        $transaction->setBudget($budget);
        $transaction->setCategory($category);
        $transaction->setTransactionCategory($transactionCategory);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $savedTransaction = $this->entityManager->getRepository(Transaction::class)->find($transaction->getId());

        $this->assertNotNull($savedTransaction);
        $this->assertEquals(TransactionTypeEnum::INCOME(), $savedTransaction->getType());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function getUser(): User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_EMAIL]);

        if (!$user) {
            $user = new User();
            $user->setEmail('test@gmail.com');
            $user->setPassword(password_hash('password', PASSWORD_BCRYPT));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }
}