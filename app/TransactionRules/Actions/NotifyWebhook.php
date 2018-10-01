<?php
/**
 * NotifyWebhook.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Transformers\TransactionTransformer;

use Illuminate\Support\Collection;
use Log;

use League\Fractal\Manager;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Fractal\Resource\Collection as FractalCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

use Symfony\Component\HttpFoundation\ParameterBag;



/**
 * Class NotifyWebhook.
 */
class NotifyWebhook implements ActionInterface
{
    /** @var RuleAction The rule action */
    private $action;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    /**
     * Notify webhook at X about the event
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        $url = $this->action->action_value;
        
        if (\filter_var($url, FILTER_VALIDATE_URL) === false) {
          Log::debug(sprintf('RuleAction NotifyWebhook invalid url "%s"', $this->action->action_value));
          return false;
        }
        
        // collect transactions using the journal collector
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        // filter on specific journals.
        $collector->setJournals(new Collection([$journal]));

        // add filter to remove transactions:
        $transactionType = $journal->transactionType->type;
        if ($transactionType === TransactionType::WITHDRAWAL) {
            $collector->addFilter(PositiveAmountFilter::class);
        }
        if (!($transactionType === TransactionType::WITHDRAWAL)) {
            $collector->addFilter(NegativeAmountFilter::class);
        }

        $transactions = $collector->getTransactions();
        $resource     = new FractalCollection($transactions, new TransactionTransformer(new ParameterBag()), 'transactions');
       
        $manager = new Manager();
        $baseUrl = route('index') . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        
        $body = $manager->createData($resource)->toArray();
        
        try {
            Log::debug(sprintf('RuleAction NotifyWebhook posting to "%s"...', $this->action->action_value));
            $client = new Client();
            $response = $client->request('POST', $this->action->action_value, ['json' => $body]);
            if ($response->getStatusCode() !== 200) {
                Log::debug(sprintf('RuleAction NotifyWebhook posted to "%s" - Webhook received a non 200 response.', $this->action->action_value));
                return false;
            }
            Log::debug(sprintf('RuleAction NotifyWebhook posted to "%s" journal id "%d".', $this->action->action_value, $journal->id));
            return true;
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() !== 410) {
              Log::debug(sprintf('RuleAction NotifyWebhook posted to "%s" - Webhook received a %d status code in response.', $this->action->action_value, $exception->getResponse()->getStatusCode()));
              return false;
              //throw new WebHookFailedException($exception->getMessage(), $exception->getCode(), $exception);
            }
        } catch (GuzzleException $exception) {
            Log::debug(sprintf('RuleAction NotifyWebhook posted to "%s" - Failed: %s', $this->action->action_value, $exception->getMessage()));
            return false;
            //throw new WebHookFailedException($exception->getMessage(), $exception->getCode(), $exception);
        }
        
        Log::debug(sprintf('RuleAction NotifyWebhook posted to "%s" - Webhook failed in posting', $this->action->action_value));
        return false;
    }
}
