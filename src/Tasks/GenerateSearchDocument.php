<?php

namespace SilverStripers\ElementalSearch\Tasks;

use Exception;
use Override;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripers\ElementalSearch\Extensions\SearchDocumentGenerator;
use SilverStripers\ElementalSearch\Extensions\SiteTreeDocumentGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class GenerateSearchDocument extends BuildTask
{
    protected static string $commandName = 'make-search-docs';

    protected string $title = 'Re-generate all search documents';

    protected static string $description = 'Generate search documents for items.';

    #[Override]
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        set_time_limit(50000);
        $classes = $this->getAllSearchDocClasses();
        foreach ($classes as $class) {
            foreach (DataList::create($class) as $record) {
                $output->writeln(sprintf(
                    'Making record for %s type %s, link %s',
                    $record->getTitle(),
                    $record->ClassName,
                    ClassInfo::hasMethod($record, 'getGenerateSearchLink')
                        ? $record->getGenerateSearchLink()
                        : $record->Title
                ));

                try {
                    SearchDocumentGenerator::make_document_for($record);
                } catch (Exception) {
                    // @TODO (SS6 upgrade) log failure without interrupting batch
                }
            }
        }

        return Command::SUCCESS;
    }

    public function getAllSearchDocClasses()
    {
        $list = [];
        foreach (ClassInfo::subclassesFor(DataObject::class) as $class) {
            $configs = Config::inst()->get($class, 'extensions', Config::UNINHERITED);
            if ($configs) {
                $valid = in_array(SearchDocumentGenerator::class, $configs)
                    || in_array(SiteTreeDocumentGenerator::class, $configs);

                if ($valid) {
                    $list[] = $class;
                }
            }
        }

        return $list;
    }
}
