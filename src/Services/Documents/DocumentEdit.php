<?php
namespace EvolutionCMS\DocumentManager\Services\Documents;

use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;
use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\User;
use Illuminate\Support\Facades\Lang;

class DocumentEdit extends DocumentCreate
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
     * @var string
     */
    protected $mode = 'edit';

    /**
     * UserRegistration constructor.
     * @param  array  $documentData
     * @param  bool  $events
     * @param  bool  $cache
     */
    public function __construct(array $documentData, bool $events = true, bool $cache = true)
    {
        $this->validate = $this->getValidationRules();
        $this->messages = $this->getValidationMessages();
        $this->documentData = $documentData;
        $this->events = $events;
        $this->cache = $cache;
        $this->currentDate = EvolutionCMS()->timestamp((int) get_by_key($_SERVER, 'REQUEST_TIME', 0));
    }

    /**
     * @return \string[][]
     */
    public function getValidationRules(): array
    {
        return [
            'id' => ['required'],
            'pagetitle' => ['required'],
            'template' => ['required'],
        ];
    }

    /**
     * @return array
     */
    public function getValidationMessages(): array
    {
        return [
            'id.required' => Lang::get('global.required_field', ['field' => 'id']),
            'pagetitle.required' => Lang::get('global.required_field', ['field' => 'pagetitle']),
            'template.required' => Lang::get('global.required_field', ['field' => 'template']),
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

        $this->prepareDocument();
        if (isset($this->documentData['pagetitle'])) {
            $this->prepareAliasDocument();
        }

        if ($this->events) {
            // invoke OnBeforeDocSave event
            EvolutionCMS()->invokeEvent('OnBeforeDocSave', [
                'action' => 'update',
                'id' => &$this->documentData['id'],
                'documentData' => &$this->documentData,
                'document' => &$document, // allow reassign object
            ]);
        }

        $updated = SiteContent::query()
            ->withTrashed()
            ->find($this->documentData['id'])
            ->update($this->documentData);

        $this->prepareTV();
        $this->saveTVs();
        $this->updateParent($this->documentData['parent']);
        $this->updateParent($this->documentData['parent_old']);
        $this->secureWebDocument($this->documentData['id']);
        $this->secureMgrDocument($this->documentData['id']);

        $document->refresh();

        if ($this->events) {
            // invoke OnDocSave event
            EvolutionCMS()->invokeEvent('OnDocSave', [
                'action' => 'update',
                'id' => &$this->documentData['id'],
                'document' => &$document, // allow reassign object
            ]);
        }

        $_SESSION['itemname'] = $this->documentData['pagetitle'];

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
        return EvolutionCMS()->hasPermission('edit_document');
    }

    public function prepareDocument()
    {
        // old document before save
        $existingDocument = SiteContent::query()
            ->withTrashed()
            ->find($this->documentData['id'])
            ->toArray();

        $this->documentData['parent_old'] = $existingDocument['parent'];

        if (!isset($this->documentData['parent'])) {
            $this->documentData['parent'] = $this->documentData['parent_old'];
        }
        if (!isset($this->documentData['template'])) {
            $this->documentData['template'] = $existingDocument['template'];
        }

        if ($this->documentData['id'] == EvolutionCMS()->getConfig('site_start') && (int) $this->documentData['published'] == 0) {
            throw new ServiceActionException('Document is linked to \'site_start\' variable and cannot be unpublished!');
        }

        $this->preparePublicationStatus();

        $today = EvolutionCMS()->timestamp();
        if ($this->documentData['id'] == EvolutionCMS()->getConfig('site_start') && ($this->documentData['pub_date'] > $today || $this->documentData['unpub_date'] != 0)) {
            throw new ServiceActionException('Document is linked to \'site_start\' variable and cannot have publish or unpublish dates set!');
        }
        if ($this->documentData['parent'] == $this->documentData['id']) {
            throw new ServiceActionException('Document can not be it\'s own parent!');
        }

        $parents = EvolutionCMS()->getParentIds($this->documentData['parent']);
        if (in_array($this->documentData['id'], $parents)) {
            throw new ServiceActionException('Document descendant can not be it\'s parent!');
        }

        // check to see document is a folder
        $child_exist = SiteContent::query()
            ->withTrashed()
            ->select('id')
            ->where('parent', $this->documentData['id'])
            ->exists();
        if ($child_exist) {
            $this->documentData['isfolder'] = 1;
        }

        // not in active parent = deleted child
        $parentDeleted = $this->documentData['parent'] > 0 && empty(SiteContent::find($this->documentData['parent']));
        if ($parentDeleted) {
            $resourceArray['deleted'] = 1;
        }

        // set publishedon and publishedby
        $isPublished = $existingDocument['published'];

        // keep original publish state, if change is not permitted
        if (EvolutionCMS()->hasPermission('publish_document')) {
            // if it was changed from unpublished to published
            if (!$isPublished && $this->documentData['published']) {
                $this->documentData['publishedon'] = $this->currentDate;
                $this->documentData['publishedby'] = EvolutionCMS()->getLoginUserID();
            } elseif ((!empty($this->documentData['pub_date']) && $this->documentData['pub_date'] <= $this->currentDate && $this->documentData['published'])) {
                $this->documentData['publishedon'] = $this->documentData['pub_date'];
                $this->documentData['publishedby'] = EvolutionCMS()->getLoginUserID();
            } elseif ($isPublished && !$this->documentData['published']) {
                $this->documentData['publishedon'] = 0;
                $this->documentData['publishedby'] = 0;
            } else {
                $this->documentData['publishedon'] = $existingDocument['publishedon'];
                $this->documentData['publishedby'] = $existingDocument['publishedby'];
            }
        } else {
            // save publishing if not permitted
            $this->documentData['published'] = $isPublished;
            $this->documentData['pub_date'] = $existingDocument['pub_date'];
            $this->documentData['unpub_date'] = $existingDocument['unpub_date'];
        }
    }

    // determine published status
    protected function preparePublicationStatus()
    {
        $today = EvolutionCMS()->timestamp();

        if (empty($this->documentData['pub_date'])) {
            $this->documentData['pub_date'] = 0;
        } else {
            $this->documentData['pub_date'] = EvolutionCMS()->toTimeStamp($this->documentData['pub_date']);

            if ($this->documentData['pub_date'] <= $today) {
                $this->documentData['published'] = 1;
            } elseif ($this->documentData['pub_date'] > $today) {
                $this->documentData['published'] = 0;
            }
        }

        if (empty($this->documentData['unpub_date'])) {
            $this->documentData['unpub_date'] = 0;
        } else {
            $this->documentData['unpub_date'] = EvolutionCMS()->toTimeStamp($this->documentData['unpub_date']);

            if ($this->documentData['unpub_date'] <= $today) {
                $this->documentData['published'] = 0;
            }
        }
    }
}
