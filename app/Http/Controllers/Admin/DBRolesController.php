<?php

namespace App\Http\Controllers\Admin;

use App\DBRole;
use App\Http\Requests\Admin\DBRole\PostRequest;

class DBRolesController extends BaseController
{
    private $links;

    public function __construct()
    {
        parent::__construct();

        $this->middleware('auth');
        $this->middleware('view.permission');

        $this->links = [
            'index_permissions' => $this->locale . '/admin/permissions',
            'new_permissions' => $this->locale . '/admin/permissions/new',
            'edit_permissions' => $this->locale . '/admin/permissions/edit',
        ];
    }

    public function index()
    {
        $permissions = DBRole::select(\DB::raw('db_roles.*'));

        $filter = \DataFilter::source($permissions);
        $filter->text('src', 'Search')->scope('freesearch');
        $filter->build();

        $grid = \DataGrid::source($filter);
        $grid->add('id', '#');
        $grid->add('name', 'Name', true);
        $grid->add('http_permission', 'HTTP Permission', true);
        $grid->add('active', 'Active', true)->cell(function ($value) {
            if ($value) {
                return __('Yes');
            }

            return __('No');
        });
        $grid->add('created_at', 'Created at', true);
        $grid->orderBy('id', 'asc');
        $grid->paginate(10);
        $grid->edit($this->links['edit_permissions'], null, 'modify|delete');

        return view('admin.permissions.index', [
            'grid' => $grid,
            'filter' => $filter,
            'links' => $this->links,
        ]);
    }

    public function create(PostRequest $request)
    {
        $model = new DBRole();

        $form = $this->buildForm($model, __('New permission'));
        $form->saved(function () use ($form) {
            alert()->success(__('Record created successfully'));
            return redirect($this->links['new_permissions']);
        });

        $form->build();

        return $form->view('admin.permissions.create', [
            'form' => $form,
            'links' => $this->links,
        ]);
    }

    public function edit(PostRequest $request)
    {
        if (request()->has('delete')) {
            $model = DBRole::find(request()->get('delete'));

            if ($model->delete()) {
                alert()->success(__('Record deleted successfully'));
                return redirect($this->links['index_permissions']);
            }

            alert()->error(__('Record not deleted'));
            return redirect($this->links['index_permissions']);
        }

        $model = DBRole::find(request()->get('modify'));

        $form = $this->buildForm($model, __('Edit permission'));
        $form->saved(function () use ($model, $request) {
            alert()->success(__('Record updated successfully'));
            return redirect($this->links['edit_permissions'] . '?modify=' . $model->id);
        });
        $form->build();

        return $form->view('admin.permissions.create', [
            'form' => $form,
            'links' => $this->links,
        ]);
    }

    private function buildForm($model, $label = null)
    {
        $form = \DataForm::source($model);

        if (!is_null($label)) {
            $form->label($label);
        }

        $form->add('name', 'Name', 'text');
        $form->add('http_permission', 'HTTP Permission', 'checkboxgroup')->options([
            'get' => 'GET',
            'post' => 'POST',
            'put' => 'PUT/PATCH',
            'delete' => 'DELETE',
        ]);
        $form->add('active', 'Active', 'checkbox');

        $form->submit('Save');

        return $form;
    }
}
