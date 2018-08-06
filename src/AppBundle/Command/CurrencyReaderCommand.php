<?php
/**
 * Created by PhpStorm.
 * User: Mārtiņš
 * Date: 02.08.2018
 * Time: 23:21
 */

namespace AppBundle\Command;


use AppBundle\Client\BackendClient;
use AppBundle\Entity\CurrencyRate;
use AppBundle\Repository\CurrencyRateRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CurrencyReaderCommand extends ContainerAwareCommand
{

    /** @var string*/
    private $currencyRateUrl;
    /** @var BackendClient */
    private $backendClient;
    /** @var EntityManager */
    private $entityManager;
    /** @var CurrencyRate */
    private $entity;
    /** @var \DateTime */
    private $todayDate;
    /** @var CurrencyRateRepository */
    private $repository;

    public function configure()
    {
        $this
        ->setName('app:fetch-currency-rates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->setUp($container);

        $previousIterationDate = $this->previousFetchedDataDate();
        //Check if it's not to early to run cron, or if there is any data at all.
        if ($previousIterationDate === false){
            //throw something like: data about this date already exists
            $output->writeln('Data about todays date are already fetched' . date('Y-m-d'));
            return;
        }

        $response = $this->backendClient->performRequest($this->currencyRateUrl);
        if (!$response){
            $output->writeln('No data were fetched from bank.lv');
            //throw something
            return;
        }

        $this->saveData($response);
        $output->writeln('Currency rates about ' . date('Y-m-d') . ' are fetched');
    }

    public function setUp(ContainerInterface $container)
    {
        $this->currencyRateUrl = $container->getParameter('rss_url');
        $this->backendClient = new BackendClient();
        $this->entity = new CurrencyRate();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->todayDate = new \DateTime();
        $this->repository = $container->get('doctrine')->getRepository('AppBundle:CurrencyRate');
    }

    public function saveData(array $currencyRates)
    {
        $createdAt = new \DateTime(date('Y-m-d H:i:s', strtotime($currencyRates['Date'])));
        foreach ($currencyRates['Currencies']['Currency'] as $currencyRate) {
            $this->entity->setCountryId($currencyRate['ID']);
            $this->entity->setRate($currencyRate['Rate']);
            $this->entity->setCreatedAt($createdAt);
            $this->entityManager->persist($this->entity);
            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }

    public function previousFetchedDataDate()
    {
        /** @var CurrencyRate $response */
        $response = $this->repository
            ->createQueryBuilder('cr')
            ->orderBy('cr.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$response) {
            return true;
        }

        $diff = $response->getCreatedAt()->diff($this->todayDate);
        if ($diff->h < 24){
            return false;
        }
        return true;
    }
}