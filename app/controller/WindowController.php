<?php

namespace app\controller;

use app\model\QueueNumber;
use app\model\Window;
use support\Request;
use support\Response;

class WindowController
{
    /**
     * 窗口列表页面
     */
    public function index()
    {
        $windows = Window::orderBy('id', 'asc')->get();
        return view('window/index', ['windows' => $windows]);
    }

    /**
     * 保存窗口
     */
    public function save(Request $request): Response
    {
        $id = $request->post('id');

        $data = [
            'name' => $request->post('name'),
            'description' => $request->post('description'),
            'status' => $request->post('status', Window::STATUS_ENABLED),
        ];

        // 验证数据
        if (!$data['name']) {
            return json(['code' => 1, 'msg' => '请填写窗口名称']);
        }

        if (strlen($data['name']) > 50) {
            return json(['code' => 1, 'msg' => '窗口名称不能超过50个字符']);
        }

        if (strlen($data['description']) > 255) {
            return json(['code' => 1, 'msg' => '描述不能超过255个字符']);
        }

        // 检查名称是否重复
        $exists = Window::where('name', $data['name'])
            ->when($id, function ($query) use ($id) {
                $query->where('id', '!=', $id);
            })
            ->exists();

        if ($exists) {
            return json(['code' => 1, 'msg' => '窗口名称已存在']);
        }

        try {
            if ($id) {
                Window::where('id', $id)->update($data);
            } else {
                Window::create($data);
            }

            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }

    /**
     * 删除窗口
     */
    public function delete(Request $request): Response
    {
        $id = $request->post('id');

        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // 检查窗口是否有正在处理的号码
        $window = Window::find($id);

        if (!$window) {
            return json(['code' => 1, 'msg' => '窗口不存在']);
        }

        $hasProcessing = $window
            ->queueNumbers()
            ->whereDate('created_at', date('Y-m-d'))
            ->whereIn('status', [
                QueueNumber::STATUS_CALLING,
                QueueNumber::STATUS_PASSED
            ])
            ->exists();

        if ($hasProcessing) {
            return json(['code' => 1, 'msg' => '该窗口有正在处理的号码，无法删除']);
        }

        try {
            $window->delete();
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }
} 