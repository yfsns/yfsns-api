<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>腾讯云短信模板管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-chat-dots"></i> 腾讯云短信模板管理</h2>
                    <div>
                        <button id="syncBtn" class="btn btn-primary me-2">
                            <i class="bi bi-arrow-repeat"></i> 同步模板
                        </button>
                        <button id="refreshBtn" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> 刷新
                        </button>
                    </div>
                </div>

                <!-- 统计信息 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-primary" id="totalCount">0</h5>
                                <p class="card-text">总模板数</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success" id="approvedCount">0</h5>
                                <p class="card-text">已通过</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning" id="pendingCount">0</h5>
                                <p class="card-text">待审核</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-info" id="enabledCount">0</h5>
                                <p class="card-text">已启用</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 模板列表 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">模板列表</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="templatesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>模板名称</th>
                                        <th>模板内容</th>
                                        <th>审核状态</th>
                                        <th>启用状态</th>
                                        <th>更新时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="templatesTableBody">
                                    <!-- 动态加载 -->
                                </tbody>
                            </table>
                        </div>

                        <!-- 加载提示 -->
                        <div id="loadingSpinner" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">加载中...</span>
                            </div>
                        </div>

                        <!-- 空数据提示 -->
                        <div id="emptyMessage" class="text-center py-4 d-none">
                            <i class="bi bi-info-circle text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">暂无模板数据，请先同步模板</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 模态框：同步结果 -->
    <div class="modal fade" id="syncModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">同步结果</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="syncResult">
                    <!-- 同步结果显示在这里 -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // API基础路径
        const API_BASE = '/api/plugins/tencent-sms';

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            loadTemplates();
            setupEventListeners();
        });

        // 设置事件监听器
        function setupEventListeners() {
            // 同步按钮
            document.getElementById('syncBtn').addEventListener('click', syncTemplates);

            // 刷新按钮
            document.getElementById('refreshBtn').addEventListener('click', loadTemplates);
        }

        // 加载模板列表
        async function loadTemplates() {
            try {
                showLoading();

                const response = await fetch(`${API_BASE}/config/templates`);
                const result = await response.json();

                if (result.success) {
                    renderTemplates(result.data);
                    updateStatistics(result.data);
                } else {
                    showError('加载模板失败：' + (result.message || '未知错误'));
                }
            } catch (error) {
                showError('网络错误：' + error.message);
            } finally {
                hideLoading();
            }
        }

        // 渲染模板列表
        function renderTemplates(templates) {
            const tbody = document.getElementById('templatesTableBody');
            tbody.innerHTML = '';

            if (!templates || templates.length === 0) {
                document.getElementById('emptyMessage').classList.remove('d-none');
                return;
            }

            document.getElementById('emptyMessage').classList.add('d-none');

            templates.forEach(template => {
                const row = createTemplateRow(template);
                tbody.appendChild(row);
            });
        }

        // 创建模板行
        function createTemplateRow(template) {
            const row = document.createElement('tr');

            // 审核状态样式
            const statusClass = getAuditStatusClass(template.audit_status);
            const statusText = getAuditStatusText(template.audit_status);

            // 启用状态
            const enabledSwitch = createEnabledSwitch(template);

            row.innerHTML = `
                <td>${template.template_id}</td>
                <td>${template.template_name || '-'}</td>
                <td>
                    <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;" title="${template.template_content}">
                        ${template.template_content}
                    </div>
                </td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>${enabledSwitch.outerHTML}</td>
                <td>${new Date(template.updated_at).toLocaleString()}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="testTemplate('${template.template_id}')">
                        <i class="bi bi-send"></i> 测试
                    </button>
                </td>
            `;

            return row;
        }

        // 创建启用状态开关
        function createEnabledSwitch(template) {
            const container = document.createElement('div');
            container.className = 'form-check form-switch';

            const input = document.createElement('input');
            input.className = 'form-check-input';
            input.type = 'checkbox';
            input.checked = template.status;
            input.addEventListener('change', () => toggleTemplateStatus(template.id, input.checked));

            const label = document.createElement('label');
            label.className = 'form-check-label';

            container.appendChild(input);
            container.appendChild(label);

            return container;
        }

        // 切换模板状态
        async function toggleTemplateStatus(templateId, enabled) {
            try {
                const response = await fetch(`${API_BASE}/config/templates/${templateId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ status: enabled })
                });

                const result = await response.json();

                if (!result.success) {
                    alert('更新状态失败：' + result.message);
                    // 恢复开关状态
                    event.target.checked = !enabled;
                } else {
                    showSuccess('状态更新成功');
                }
            } catch (error) {
                alert('网络错误：' + error.message);
                // 恢复开关状态
                event.target.checked = !enabled;
            }
        }

        // 同步模板
        async function syncTemplates() {
            const btn = document.getElementById('syncBtn');
            const originalText = btn.innerHTML;

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-arrow-repeat spinning"></i> 同步中...';

                const response = await fetch(`${API_BASE}/config/templates/sync`, {
                    method: 'POST'
                });

                const result = await response.json();
                showSyncResult(result);

            } catch (error) {
                showSyncResult({
                    success: false,
                    message: '网络错误：' + error.message
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // 测试模板
        async function testTemplate(templateId) {
            const phone = prompt('请输入测试手机号：', '18855188912');
            if (!phone) return;

            try {
                const response = await fetch(`${API_BASE}/test/send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        template_id: templateId,
                        phone: phone,
                        template_data: {}
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('测试短信发送成功！');
                } else {
                    alert('测试失败：' + result.message);
                }
            } catch (error) {
                alert('网络错误：' + error.message);
            }
        }

        // 显示同步结果
        function showSyncResult(result) {
            const modal = new bootstrap.Modal(document.getElementById('syncModal'));
            const resultDiv = document.getElementById('syncResult');

            if (result.success) {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6>同步成功！</h6>
                        <p>同步了 ${result.data?.synced_count || 0} 个模板</p>
                        ${result.data?.errors?.length ? '<p class="mb-1">错误信息：</p>' + result.data.errors.map(err => `<small class="text-danger">${err}</small>`).join('<br>') : ''}
                    </div>
                `;
                // 重新加载模板列表
                loadTemplates();
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>同步失败！</h6>
                        <p>${result.message}</p>
                        ${result.data?.errors?.length ? '<p class="mb-1">详细错误：</p>' + result.data.errors.map(err => `<small class="text-danger">${err}</small>`).join('<br>') : ''}
                    </div>
                `;
            }

            modal.show();
        }

        // 更新统计信息
        function updateStatistics(templates) {
            const total = templates.length;
            const approved = templates.filter(t => t.audit_status === 'approved').length;
            const pending = templates.filter(t => t.audit_status === 'pending').length;
            const enabled = templates.filter(t => t.status).length;

            document.getElementById('totalCount').textContent = total;
            document.getElementById('approvedCount').textContent = approved;
            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('enabledCount').textContent = enabled;
        }

        // 工具函数
        function getAuditStatusClass(status) {
            switch (status) {
                case 'approved': return 'bg-success';
                case 'pending': return 'bg-warning';
                case 'rejected': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }

        function getAuditStatusText(status) {
            switch (status) {
                case 'approved': return '已通过';
                case 'pending': return '待审核';
                case 'rejected': return '已拒绝';
                default: return '未知';
            }
        }

        function showLoading() {
            document.getElementById('loadingSpinner').classList.remove('d-none');
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').classList.add('d-none');
        }

        function showError(message) {
            // 这里可以集成更好的错误提示
            alert(message);
        }

        function showSuccess(message) {
            // 这里可以集成更好的成功提示
            console.log(message);
        }
    </script>

    <style>
        .spinning {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .table th {
            border-top: none;
            font-weight: 600;
        }

        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: none;
        }
    </style>
</body>
</html>
