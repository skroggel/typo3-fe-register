<?php
namespace Madj2k\FeRegister\Command;
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Madj2k\FeRegister\DataProtection\DataProtectionHandler;
use Madj2k\FeRegister\Domain\Repository\OptInRepository;
use Madj2k\FeRegister\Persistence\Cleaner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * class SendCommand
 *
 * Execute on CLI with: 'vendor/bin/typo3 fe_register:cleanup'
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CleanupCommand extends Command
{

    /**
     * @var \Madj2k\FeRegister\Persistence\Cleaner|null
     */
    protected ?Cleaner $cleaner = null;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setDescription('Removes expired optIns and frontendUsers.')
            ->addOption(
                'daysSinceExpired',
                'd',
                InputOption::VALUE_REQUIRED,
                'Days since optIns and frontendUsers are expired.',
                7
            )
            ->addOption(
                'dryRun',
                't',
                InputOption::VALUE_REQUIRED,
                'Do a dry-run without making changes (default: 1).',
                1
            );
    }


    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @see \Symfony\Component\Console\Input\InputInterface::bind()
     * @see \Symfony\Component\Console\Input\InputInterface::validate()
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager$objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->cleaner = $objectManager->get(Cleaner::class);
    }


    /**
     * Executes the command for showing sys_log entries
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     * @see \Symfony\Component\Console\Input\InputInterface::bind()
     * @see \Symfony\Component\Console\Input\InputInterface::validate()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $daysSinceExpired = $input->getOption('daysSinceExpired');
        $dryRun = $input->getOption('dryRun');

        $io->note('Using daysSinceExpired="' . $daysSinceExpired .'"');
        $io->note('Using dryRun="' . $dryRun .'"');

        $result = 0;
        try {

            $this->cleaner->setDryRun(boolval($dryRun));

            $cnt = $this->cleaner->removeOptIns($daysSinceExpired);
            $message = 'Removed ' . $cnt . ' optIn(s).';
            $io->note($message);
            $this->getLogger()->log(LogLevel::INFO, $message);

            $cnt = $this->cleaner->removeGuestUsers($daysSinceExpired);
            $message = 'Removed ' . $cnt . ' GuestUsers(s).';
            $io->note($message);
            $this->getLogger()->log(LogLevel::INFO, $message);

            $cnt = $this->cleaner->removeFrontendUsers($daysSinceExpired);
            $message = 'Removed ' . $cnt . ' FrontendUsers(s).';
            $io->note($message);
            $this->getLogger()->log(LogLevel::INFO, $message);

            $cnt = $this->cleaner->deleteFrontendUsers($daysSinceExpired);
            $message = 'Marked ' . $cnt . ' FrontendUsers(s) as deleted.';
            $io->note($message);
            $this->getLogger()->log(LogLevel::INFO, $message);

            $cnt = $this->cleaner->removeConsents($daysSinceExpired);
            $message = 'Removed ' . $cnt . ' Consents(s).';
            $io->note($message);
            $this->getLogger()->log(LogLevel::INFO, $message);


        } catch (\Exception $e) {

            $message = sprintf('An unexpected error occurred while trying to cleanup: %s',
                str_replace(array("\n", "\r"), '', $e->getMessage())
            );

            // @extensionScannerIgnoreLine
            $io->error($message);
            $this->getLogger()->log(LogLevel::ERROR, $message);
            $result = 1;
        }

        $io->writeln('Done');
        return $result;

    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
    {
        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }
}
