<?php

namespace App\Tests\Functional\Entity;

use App\Entity\Budget;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\TransactionCategory;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TransactionTest extends KernelTestCase
{
    private const string USER_EMAIL = 'test@gmail.com';
    private const string CURRENCY = 'EUR';
    private const string BUDGET_START_DATE = '2024-06-01';
    private const string BUDGET_END_DATE = '2024-06-30';
    private const int BUDGET_YEAR = 2024;
    private const int BUDGET_MONTH = 6;
    private const float BUDGET_START_BALANCE = 1000.00;
    private const string CATEGORY_NAME = 'Test Category';
    private const string TRANSACTION_CATEGORY_NAME = 'Test Transaction Category';
    private const float TRANSACTION_AMOUNT = 100.00;
    private const string TRANSACTION_DATE = '2024-06-01';

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
        $budget->setYear(self::BUDGET_YEAR);
        $budget->setMonth(self::BUDGET_MONTH);
        $budget->setStartDate(new DateTime(self::BUDGET_START_DATE));
        $budget->setEndDate(new DateTime(self::BUDGET_END_DATE));
        $budget->setCurrency(self::CURRENCY);
        $budget->setStartBalance(self::BUDGET_START_BALANCE);

        $this->entityManager->persist($budget);

        $category = new Category();
        $category->setName(self::CATEGORY_NAME);

        $this->entityManager->persist($category);

        $this->entityManager->flush();

        $transactionCategory = new TransactionCategory();
        $transactionCategory->setName(self::TRANSACTION_CATEGORY_NAME);

        $this->entityManager->persist($transactionCategory);

        $this->entityManager->flush();

        $transaction = new Transaction();
        $transaction->setAmount(self::TRANSACTION_AMOUNT);
        $transaction->setDate(new DateTime(self::TRANSACTION_DATE));
        $transaction->setBudget($budget);
        $transaction->setCategory($category);
        $transaction->setTransactionCategory($transactionCategory);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->assertNotNull($transaction->getId());
        $this->assertEquals(self::TRANSACTION_AMOUNT, $transaction->getAmount());
        $this->assertEquals(new DateTime(self::TRANSACTION_DATE), $transaction->getDate());
        $this->assertEquals($budget, $transaction->getBudget());
        $this->assertEquals($category, $transaction->getCategory());
        $this->assertEquals($transactionCategory, $transaction->getTransactionCategory());

        $budget->addTransaction($transaction);
        $this->entityManager->flush();

        $this->assertCount(1, $budget->getTransactions());
        $this->assertTrue($budget->getTransactions()->contains($transaction));
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
            $user->setEmail(self::USER_EMAIL);
            $user->setPassword(password_hash('password', PASSWORD_BCRYPT));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }
}