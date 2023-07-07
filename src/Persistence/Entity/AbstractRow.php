<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Persistence\Entity;

abstract class AbstractRow extends \Frootbox\Db\Row
{
    /**
     * @var array|mixed
     */
    protected array $config = [ ];

    /**
     * @var array|mixed
     */
    protected array $trackedChanges = [ ];

    /**
     *
     */
    public function __construct(array $record = null, \Frootbox\Db\Db $db = null)
    {
        parent::__construct($record, $db);

        if (!empty($this->data['config'])) {
            $this->config = !is_array($this->data['config']) ? json_decode($this->data['config'], true) : $this->data['config'];
        }
    }

    /**
     *
     */
    public function trackChanges(): array
    {
        $preData = $this->trackedChanges;

        $this->trackedChanges = array_diff($this->getData(), $this->trackedChanges);

        $changes = [];

        foreach ($this->trackedChanges as $key => $value) {

            if ($key == 'updated') {
                continue;
            }

            $changes[] = [
                'key' => ucfirst($key),
                'before' => $preData[$key] ?? null,
                'after' => $value,
            ];
        }

        return $changes;
    }
}
