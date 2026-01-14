<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinyMCE编辑器图片上传示例</title>
    
    <!-- TinyMCE CDN -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <!-- 自定义配置 -->
    <script src="{{ asset('js/tinymce-config.js') }}"></script>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .editor-container {
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .btn {
            background: #007cba;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background: #005a87;
        }
        .preview {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <h1>TinyMCE编辑器图片上传示例</h1>
    
    <form id="editorForm">
        <div class="form-group">
            <label for="title">标题</label>
            <input type="text" id="title" name="title" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div class="form-group">
            <label for="content">内容</label>
            <textarea id="content" name="content" class="tinymce">
                <p>在这里输入内容...</p>
                <p>点击图片按钮可以上传图片，支持图文混排。</p>
            </textarea>
        </div>
        
        <div class="form-group">
            <button type="button" class="btn" onclick="saveContent()">保存内容</button>
            <button type="button" class="btn" onclick="previewContent()">预览</button>
        </div>
    </form>
    
    <div id="preview" class="preview" style="display: none;">
        <h3>预览</h3>
        <div id="previewContent"></div>
    </div>
    
    <script>
        // 保存内容
        function saveContent() {
            const title = document.getElementById('title').value;
            const content = tinymce.get('content').getContent();
            
            console.log('标题:', title);
            console.log('内容:', content);
            
            // 这里可以发送到后端保存
            // fetch('/api/v1/posts', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'Authorization': 'Bearer ' + getAuthToken()
            //     },
            //     body: JSON.stringify({
            //         title: title,
            //         content: content
            //     })
            // })
            
            alert('内容已保存到控制台，请查看');
        }
        
        // 预览内容
        function previewContent() {
            const content = tinymce.get('content').getContent();
            const previewDiv = document.getElementById('preview');
            const previewContent = document.getElementById('previewContent');
            
            previewContent.innerHTML = content;
            previewDiv.style.display = 'block';
        }
        
        // 重写获取token的函数（示例）
        function getAuthToken() {
            // 这里应该返回真实的认证token
            return 'your-auth-token-here';
        }
        
        // 重写内容变化回调
        window.onEditorContentChange = function(content) {
            console.log('内容变化:', content.length + ' 字符');
        };
        
        // 重写保存回调
        window.onEditorSave = function(content) {
            saveContent();
        };
    </script>
</body>
</html>
