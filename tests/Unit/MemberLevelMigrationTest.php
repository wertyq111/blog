<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MemberLevelMigrationTest extends TestCase
{
    /**
     * 返回后端仓库下数据库迁移文件的绝对路径，供当前 schema 回归测试复用。
     */
    private function migrationPath(string $filename): string
    {
        return dirname(__DIR__, 2) . '/database/migrations/' . $filename;
    }

    public function test_member_level_deleted_at_uses_unsigned_integer_in_create_migration(): void
    {
        $migration = file_get_contents($this->migrationPath('2023_04_18_012549_create_member_level_table.php'));

        $this->assertIsString($migration);
        $this->assertStringContainsString("unsignedInteger('deleted_at')", $migration);
        $this->assertStringNotContainsString("unsignedTinyInteger('deleted_at')", $migration);
    }

    public function test_member_level_deleted_at_fix_migration_exists(): void
    {
        $migrationPath = $this->migrationPath('2026_04_02_113000_fix_member_level_deleted_at_column.php');

        $this->assertFileExists($migrationPath);
    }
}
