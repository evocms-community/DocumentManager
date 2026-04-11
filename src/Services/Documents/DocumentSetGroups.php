<?php
namespace EvolutionCMS\DocumentManager\Services\Documents;

use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;
use EvolutionCMS\Models\DocumentGroup;
use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\User;
use Illuminate\Support\Facades\Lang;

class DocumentSetGroups extends DocumentCreate
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
        $check_permissions = array_key_exists('check_permissions', $this->documentData) ? (bool) $this->documentData['check_permissions'] : true;

        if ($check_permissions && !$this->checkRules()) {
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
            // invoke OnBeforeDocSetGroups event
            EvolutionCMS()->invokeEvent('OnBeforeDocSetGroups', [
                'id' => &$this->documentData['id'],
                'groups' => &$this->documentData['document_groups'],
                'document' => &$document, // allow reassign object
            ]);
        }

        if (empty($this->documentData['document_groups'])) {
            // necessary to remove all permissions as document is public
            DocumentGroup::query()
                ->where('document', $this->documentData['id'])
                ->delete();
        } else {
            $new_groups = [];
            // process the new input
            foreach ($this->documentData['document_groups'] as $group) {
                $new_groups[$group] = $this->documentData['id'];
            }

            // grab the current set of permissions on this document
            $documentGroups = DocumentGroup::query()
                ->select('id', 'document_group')
                ->where('document', $this->documentData['id'])
                ->get();

            $old_groups = [];
            foreach ($documentGroups as $documentGroup) {
                $old_groups[$documentGroup->document_group] = $documentGroup->id;
            }

            // update the permissions in the database
            $insertions = [];
            foreach ($new_groups as $group => $link_id) {
                if (array_key_exists($group, $old_groups)) {
                    unset($old_groups[$group]);
                } else {
                    $insertions[] = [
                        'document_group' => (int) $group,
                        'document' => $this->documentData['id'],
                    ];
                }
            }
            if (!empty($insertions)) {
                DocumentGroup::query()
                    ->insert($insertions);
            }
            if (!empty($old_groups)) {
                DocumentGroup::query()
                    ->whereIn('id', $old_groups)
                    ->delete();
            }
        }

        $document->refresh();

        if ($this->events) {
            // invoke OnDocSetGroups event
            EvolutionCMS()->invokeEvent('OnDocSetGroups', [
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
        return EvolutionCMS()->hasAnyPermissions(['manage_groups', 'manage_document_permissions']);
    }
}
