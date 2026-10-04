<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Users and API tokens.
 *
 * Tables are described with the DBAL schema API and turned into SQL by the
 * current platform, so the migration runs unchanged on SQLite, PostgreSQL and MySQL.
 */
final class Version20260930120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and api_tokens tables';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->platform->getCreateTablesSQL([$this->usersTable(), $this->apiTokensTable()]) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql($this->platform->getDropTableSQL('api_tokens'));
        $this->addSql($this->platform->getDropTableSQL('users'));
    }

    private function usersTable(): Table
    {
        return Table::editor()
            ->setUnquotedName('users')
            ->setColumns(
                $this->id(),
                Column::editor()->setUnquotedName('email')->setTypeName(Types::STRING)->setLength(180)->create(),
                Column::editor()->setUnquotedName('name')->setTypeName(Types::STRING)->setLength(100)->create(),
                Column::editor()->setUnquotedName('password_hash')->setTypeName(Types::STRING)->setLength(255)->create(),
                $this->datetime('created_at'),
                $this->datetime('updated_at'),
            )
            ->setPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create())
            ->setIndexes($this->uniqueIndex('uniq_users_email', 'email'))
            ->create();
    }

    private function apiTokensTable(): Table
    {
        return Table::editor()
            ->setUnquotedName('api_tokens')
            ->setColumns(
                $this->id(),
                Column::editor()->setUnquotedName('user_id')->setTypeName(Types::INTEGER)->create(),
                Column::editor()->setUnquotedName('token_hash')->setTypeName(Types::STRING)->setLength(64)->create(),
                $this->datetime('created_at'),
                $this->datetime('expires_at'),
            )
            ->setPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create())
            ->setIndexes(
                $this->uniqueIndex('uniq_api_tokens_hash', 'token_hash'),
                Index::editor()->setUnquotedName('idx_api_tokens_user')->setUnquotedColumnNames('user_id')->create(),
            )
            ->setForeignKeyConstraints(
                ForeignKeyConstraint::editor()
                    ->setUnquotedName('fk_api_tokens_user')
                    ->setUnquotedReferencingColumnNames('user_id')
                    ->setUnquotedReferencedTableName('users')
                    ->setUnquotedReferencedColumnNames('id')
                    ->setOnDeleteAction(ReferentialAction::CASCADE)
                    ->create(),
            )
            ->create();
    }

    /**
     * Unique index rather than unique constraint: this is how the ORM maps #[ORM\UniqueConstraint],
     * so the database stays in sync with the entity mapping.
     */
    private function uniqueIndex(string $name, string $column): Index
    {
        return Index::editor()->setUnquotedName($name)->setType(IndexType::UNIQUE)->setUnquotedColumnNames($column)->create();
    }

    private function id(): Column
    {
        return Column::editor()->setUnquotedName('id')->setTypeName(Types::INTEGER)->setAutoincrement(true)->create();
    }

    private function datetime(string $name): Column
    {
        return Column::editor()->setUnquotedName($name)->setTypeName(Types::DATETIME_IMMUTABLE)->create();
    }
}
