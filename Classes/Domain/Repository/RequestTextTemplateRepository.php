<?php

namespace WapplerSystems\Inquiry\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

class RequestTextTemplateRepository extends Repository
{


    public function createQuery() {

        $query = parent::createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query;
    }


}
