<?php

declare(strict_types=1);

namespace Tests\Unit\DataTable;

use App\Support\DataTable\DataTableRowActions;
use PHPUnit\Framework\TestCase;

final class DataTableRowActionsTest extends TestCase
{
    public function testLinkProducesAnchorAction(): void
    {
        $actions = new DataTableRowActions();

        $link = $actions->link('Profile', '/admin/users/1', 'ghost');

        self::assertSame('link', $link['type']);
        self::assertSame('Profile', $link['label']);
        self::assertSame('/admin/users/1', $link['href']);
        self::assertSame('ghost', $link['variant']);
        self::assertArrayNotHasKey('permission', $link);
    }

    public function testLinkWithPermissionAddsGate(): void
    {
        $actions = new DataTableRowActions();

        $link = $actions->link('Edit', '/admin/users/1/edit', 'secondary', 'users.edit');

        self::assertSame('link', $link['type']);
        self::assertSame('users.edit', $link['permission']);
    }

    public function testFormButtonProducesFormAction(): void
    {
        $actions = new DataTableRowActions();

        $button = $actions->formButton('Delete', '/admin/users/1/delete', 'danger');

        self::assertSame('form_button', $button['type']);
        self::assertSame('Delete', $button['label']);
        self::assertSame('/admin/users/1/delete', $button['action']);
        self::assertSame('danger', $button['variant']);
        self::assertArrayNotHasKey('icon', $button);
        self::assertArrayNotHasKey('permission', $button);
        self::assertArrayNotHasKey('confirm', $button);
    }

    public function testFormButtonIncludesIconAndPermissionAndConfirm(): void
    {
        $actions = new DataTableRowActions();

        $button = $actions->formButton(
            'Delete',
            '/admin/users/1/delete',
            'danger',
            'trash-2',
            'users.delete',
            'Are you sure?'
        );

        self::assertSame('trash-2', $button['icon']);
        self::assertSame('users.delete', $button['permission']);
        self::assertSame('Are you sure?', $button['confirm']);
    }

    public function testDisabledButtonMarksActionAsInactiveWithTitle(): void
    {
        $actions = new DataTableRowActions();

        $button = $actions->disabledButton('Delete', 'Cannot delete last admin.');

        self::assertSame('button', $button['type']);
        self::assertTrue($button['disabled']);
        self::assertSame('Delete', $button['label']);
        self::assertSame('Cannot delete last admin.', $button['title']);
        self::assertSame('danger', $button['variant']);
    }
}
