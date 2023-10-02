<?php
/**
 *
 */

namespace OmniTools\Migration;

abstract class AbstractMigration
{
    protected $description;
    protected $log;

    /**
     * @param \DI\Container $container
     */
    public function __construct(
        protected \DI\Container $container
    ) {}

    /**
     * @param string $sql
     * @return void
     */
    protected function executeSql(string $sql): void
    {
        try {
            $this->getDb()->query($sql);
        }
        catch ( \Exception $e ) {
            // Ignore
        }
    }

    /**
     * @return \Frootbox\Db\Db
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function getDb(): \Frootbox\Db\Db
    {
        return $this->container->get(\Frootbox\Db\Db::class);
    }

    /**
     * @return string|null
     */
    protected function getLog(): ?string
    {
        return $this->log === null ? null : trim($this->log);
    }

    /**
     * @param string $log
     * @return void
     */
    protected function log(string $log): void
    {
        $this->log .= "\n" . $log;
    }

    /**
     * Perform upwards migration
     */
    abstract protected function up(): void;

    /**
     * @return string
     */
    public function __toString(): string
    {
        return 'Migration ' . $this->getVersion();
    }

    /**
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Frootbox\Exceptions\InputInvalid
     */
    public function execute(): void
    {
        // Obtain user
        $user = $this->container->get(\OmniTools\Persistence\Entity\User::class);

        // Check if migration was already executed
        $migrationRepository = $this->getDb()->getRepository(\OmniTools\Persistence\Repository\Migration::class);

        $check = $migrationRepository->fetchOne([
            'where' => [
                'version' => $this->getVersion(),
            ],
        ]);

        if (!empty($check)) {
            $date = new \DateTime($check->getDate());
            throw new \Frootbox\Exceptions\InputInvalid('Die Migration wurde bereits am ' . $date->format('d.m.Y') . ' um ' . $date->format('H:i') . ' Uhr ausgeführt.');
        }


        // Perform upwards migration
        $this->up();

        // Store migration execution
        $migration = new \OmniTools\Persistence\Entity\Migration([
            'version' => $this->getVersion(),
            'userId' => $user->getId(),
            'log' => $this->getLog(),
        ]);

        $migrationRepository->persist($migration);
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return implode('.', str_split($this->getVersionRaw(), 2));
    }

    /**
     * @return string
     */
    public function getVersionRaw(): string
    {
        return substr(get_class($this), -6);
    }
}
