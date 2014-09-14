<?php

namespace Firefly\Storage\Category;

use Illuminate\Queue\Jobs\Job;

/**
 * Class EloquentCategoryRepository
 *
 * @package Firefly\Storage\Category
 */
class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    protected $_user = null;

    /**
     *
     */
    public function __construct()
    {
        $this->_user = \Auth::user();
    }

    /**
     * Takes a transfer/category component and updates the transaction journal to match.
     *
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importUpdateTransfer(Job $job, array $payload) {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);


        if ($job->attempts() > 10) {
            \Log::error('Never found category/transfer combination "' . $payload['data']['transfer_id'] . '"');

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed.
            return;
        }


        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $journals->overruleUser($user);

        /*
         * Prep some vars from the payload
         */
        $transferId = intval($payload['data']['transfer_id']);
        $componentId   = intval($payload['data']['component_id']);

        /*
         * Find the import map for both:
         */
        $categoryMap    = $repository->findImportEntry($importMap, 'Category', $componentId);
        $transferMap = $repository->findImportEntry($importMap, 'Transfer', $transferId);

        /*
         * Either may be null:
         */
        if (is_null($categoryMap) || is_null($transferMap)) {
            \Log::notice('No map found in category/transfer mapper. Release.');
            if(\Config::get('queue.default') == 'sync') {
                $importMap->jobsdone++;
                $importMap->save();
                $job->delete(); // count fixed
            } else {
                $job->release(300); // proper release.
            }
            return;
        }

        /*
         * Find the budget and the transaction:
         */
        $category = $this->find($categoryMap->new);
        /** @var \TransactionJournal $journal */
        $journal = $journals->find($transferMap->new);

        /*
         * If either is null, release:
         */
        if (is_null($category) || is_null($journal)) {
            \Log::notice('Map is incorrect in category/transfer mapper. Release.');
            if(\Config::get('queue.default') == 'sync') {
                $importMap->jobsdone++;
                $importMap->save();
                $job->delete(); // count fixed
            } else {
                $job->release(300); // proper release.
            }
            return;
        }

        /*
         * Update journal to have budget:
         */
        $journal->categories()->save($category);
        $journal->save();
        \Log::debug('Connected category "' . $category->name . '" to journal "' . $journal->description . '"');

        $importMap->jobsdone++;
        $importMap->save();

        $job->delete(); // count fixed


        return;
    }

    /**
     * Takes a transaction/category component and updates the transaction journal to match.
     *
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importUpdateTransaction(Job $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);


        if ($job->attempts() > 10) {
            \Log::error('Never found category/transaction combination "' . $payload['data']['transaction_id'] . '"');

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed.
            return;
        }


        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $journals->overruleUser($user);

        /*
         * Prep some vars from the payload
         */
        $transactionId = intval($payload['data']['transaction_id']);
        $componentId   = intval($payload['data']['component_id']);

        /*
         * Find the import map for both:
         */
        $categoryMap    = $repository->findImportEntry($importMap, 'Category', $componentId);
        $transactionMap = $repository->findImportEntry($importMap, 'Transaction', $transactionId);

        /*
         * Either may be null:
         */
        if (is_null($categoryMap) || is_null($transactionMap)) {
            \Log::notice('No map found in category/transaction mapper. Release.');
            if(\Config::get('queue.default') == 'sync') {
                $importMap->jobsdone++;
                $importMap->save();
                $job->delete(); // count fixed
            } else {
                $job->release(300); // proper release.
            }
            return;
        }

        /*
         * Find the budget and the transaction:
         */
        $category = $this->find($categoryMap->new);
        /** @var \TransactionJournal $journal */
        $journal = $journals->find($transactionMap->new);

        /*
         * If either is null, release:
         */
        if (is_null($category) || is_null($journal)) {
            \Log::notice('Map is incorrect in category/transaction mapper. Release.');
            if(\Config::get('queue.default') == 'sync') {
                $importMap->jobsdone++;
                $importMap->save();
                $job->delete(); // count fixed
            } else {
                $job->release(300); // proper release.
            }
            return;
        }

        /*
         * Update journal to have budget:
         */
        $journal->categories()->save($category);
        $journal->save();
        \Log::debug('Connected category "' . $category->name . '" to journal "' . $journal->description . '"');

        $importMap->jobsdone++;
        $importMap->save();

        $job->delete(); // count fixed


        return;
    }

    /**
     * @param \User $user
     *
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        return true;
    }

    /**
     * @param $categoryId
     *
     * @return mixed
     */
    public function find($categoryId)
    {
        return $this->_user->categories()->find($categoryId);
    }

    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importCategory(Job $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        /*
         * Maybe the category has already been imported
         */
        $importEntry = $repository->findImportEntry($importMap, 'Category', intval($payload['data']['id']));

        /*
         * if so, delete job and return:
         */
        if (!is_null($importEntry)) {
            \Log::debug('Already imported category ' . $payload['data']['name']);

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }

        /*
         * try to find category first
         */
        $current = $this->findByName($payload['data']['name']);

        /*
         * If not found, create it:
         */
        if (is_null($current)) {
            $category = $this->store($payload['data']);
            $repository->store($importMap, 'Category', $payload['data']['id'], $category->id);
            \Log::debug('Imported category "' . $payload['data']['name'] . '".');
        } else {
            $repository->store($importMap, 'Category', $payload['data']['id'], $current->id);
            \Log::debug('Already had category "' . $payload['data']['name'] . '".');
        }

        // update map:
        $importMap->jobsdone++;
        $importMap->save();

        $job->delete(); // count fixed
        return;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function findByName($name)
    {
        if ($name == '' || strlen($name) == 0) {
            return null;
        }

        return $this->_user->categories()->where('name', $name)->first();

    }

    /**
     * @param $data
     *
     * @return \Category|mixed
     */
    public function store($data)
    {
        $category       = new \Category;
        $category->name = $data['name'];

        $category->user()->associate($this->_user);
        $category->save();

        return $category;
    }

    /**
     * @param $name
     *
     * @return \Category|mixed
     */
    public function createOrFind($name)
    {
        if (strlen($name) == 0) {
            return null;
        }
        $category = $this->findByName($name);
        if (!$category) {
            return $this->store(['name' => $name]);
        }

        return $category;


    }

    /**
     * @param $category
     *
     * @return bool|mixed
     */
    public function destroy($category)
    {
        $category->delete();

        return true;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->_user->categories()->orderBy('name', 'ASC')->get();
    }

    /**
     * @param $category
     * @param $data
     *
     * @return mixed
     */
    public function update($category, $data)
    {
        // update account accordingly:
        $category->name = $data['name'];
        if ($category->validate()) {
            $category->save();
        }

        return $category;
    }
} 