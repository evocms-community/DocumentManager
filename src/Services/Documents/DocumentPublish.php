<?php
namespace EvolutionCMS\DocumentManager\Services\Documents;

use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;
use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\User;
use Illuminate\Support\Facades\Lang;

class DocumentPublish extends DocumentCreate
{
    /**
     * @var \string[][]
     */
    public $validate;

    /**
     * @var array
     */
    public $messages;

    /**
     * @var array
     */
    public $documentData;

    /**
     * @var bool
     */
    public $events;

    /**
     * @var bool
     */
    public $cache;

    /**
     * @var array $validateErrors
     */
    public $validateErrors;

    /**
     * @var int
     */
    public $currentDate;

    /**
     * @var array
     */
    public $tvs = [];

    /**
     * UserRegistration constructor.
     * @param array $documentData
     * @param bool $events
     * @param bool $cache
     */
    public function __construct(array $documentData, bool $events = true, bool $cache = true)
    {
        $this->validate = $this->getValidationRules();
        $this->messages = $this->getValidationMessages();
        $this->documentData = $documentData;
        $this->events = $events;
        $this->cache = $cache;
    }

    /**
     * @return \string[][]
     */
    public function getValidationRules(): array
    {
        return [
            'id' => ['required'],
        ];
    }

    /**
     * @return array
     */
    public function getValidationMessages(): array
    {
        return [
            'id.required' => Lang::get('global.required_field', ['field' => 'id']),
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @throws ServiceActionException
     * @throws ServiceValidationException
     */
    public function process(): \Illuminate\Database\Eloquent\Model
    {
        if (!$this->checkRules()) {
            throw new ServiceActionException(Lang::get('global.error_no_privileges'));
        }

        if (!$this->validate()) {
            $exception = new ServiceValidationException();
            $exception->setValidationErrors($this->validateErrors);
            throw $exception;
        }

        $document = SiteContent::query()
            ->withTrashed()
            ->find($this->documentData['id']);

        if ($this->events) {
            // invoke OnBeforeDocPublish event
            EvolutionCMS()->invokeEvent('OnBeforeDocPublish', [
                'id' => &$this->documentData['id'],
                'document' => &$document, // allow reassign object
            ]);
        }

        $document->published = 1;
        $document->publishedby = EvolutionCMS()->getLoginUserID();
        $document->publishedon = evo()->timestamp();

        if ($document->pub_date > $document->publishedon) {
            $document->pub_date = 0;
        }
        // $document->unpub_date = 0;

        $document->save();
        $document->refresh();

        if ($this->events) {
            // invoke OnDocPublish event
            EvolutionCMS()->invokeEvent('OnDocPublish', [
                'id' => &$this->documentData['id'],
                'document' => &$document, // allow reassign object
            ]);
        }

        if ($this->cache) {
            EvolutionCMS()->clearCache('full');
        }

        return $document;
    }

    /**
     * @return bool
     */
    public function checkRules(): bool
    {
        return EvolutionCMS()->hasPermission('publish_document');
    }
}
