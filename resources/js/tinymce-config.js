// TinyMCE编辑器配置
window.tinymceConfig = {
  // 基础配置
  selector: 'textarea.tinymce',
  height: 400,
  language: 'zh_CN',
  
  // 工具栏配置
  toolbar: [
    'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify',
    'outdent indent | numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen preview save print',
    'insertfile image media table link anchor codesample | ltr rtl'
  ],
  
  // 插件配置
  plugins: [
    'advlist autolink lists link image charmap print preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table paste code help wordcount',
    'emoticons'
  ],
  
  // 图片上传配置
  images_upload_url: '/api/v1/editor/upload-image',
  images_upload_handler: function (blobInfo, success, failure, progress) {
    // 创建FormData
    const formData = new FormData();
    formData.append('file', blobInfo.blob(), blobInfo.filename());
    
    // 发送请求
    fetch('/api/v1/editor/upload-image', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + getAuthToken(), // 获取认证token的函数
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    })
    .then(response => response.json())
    .then(result => {
      if (result.location) {
        success(result.location);
      } else {
        failure(result.error || '上传失败');
      }
    })
    .catch(error => {
      console.error('图片上传失败:', error);
      failure('上传失败，请重试');
    });
  },
  
  // 图片上传进度显示
  images_upload_base_path: '',
  
  // 自动上传
  automatic_uploads: true,
  
  // 文件类型过滤
  file_picker_types: 'image',
  
  // 图片处理配置
  image_advtab: true,
  image_description: false,
  image_title: true,
  
  // 媒体配置
  media_live_embeds: true,
  
  // 内容样式
  content_style: `
    body { 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      font-size: 14px;
      line-height: 1.6;
      color: #333;
    }
    img {
      max-width: 100%;
      height: auto;
    }
    table {
      border-collapse: collapse;
      width: 100%;
    }
    table td, table th {
      border: 1px solid #ddd;
      padding: 8px;
    }
  `,
  
  // 其他配置
  menubar: true,
  branding: false,
  promotion: false,
  
  // 初始化回调
  init_instance_callback: function (editor) {
    console.log('TinyMCE编辑器初始化完成');
    
    // 可以在这里添加自定义功能
    editor.on('change', function () {
      // 内容变化时的处理
      const content = editor.getContent();
      // 触发内容变化事件
      if (window.onEditorContentChange) {
        window.onEditorContentChange(content);
      }
    });
  },
  
  // 设置回调
  setup: function (editor) {
    // 添加自定义按钮或功能
    editor.ui.registry.addButton('customSave', {
      text: '保存',
      onAction: function () {
        const content = editor.getContent();
        if (window.onEditorSave) {
          window.onEditorSave(content);
        }
      }
    });
  }
};

// 获取认证token的函数（需要根据实际项目调整）
function getAuthToken() {
  // 从localStorage获取token
  return localStorage.getItem('auth_token') || '';
  
  // 或者从cookie获取
  // return getCookie('auth_token');
  
  // 或者从全局变量获取
  // return window.authToken;
}

// 内容变化回调函数（可选）
window.onEditorContentChange = function(content) {
  console.log('编辑器内容变化:', content);
  // 可以在这里实现自动保存等功能
};

// 保存回调函数（可选）
window.onEditorSave = function(content) {
  console.log('保存编辑器内容:', content);
  // 可以在这里实现保存逻辑
};

// 初始化编辑器
function initTinyMCE() {
  if (typeof tinymce !== 'undefined') {
    tinymce.init(window.tinymceConfig);
  } else {
    console.error('TinyMCE未加载，请确保已引入TinyMCE库');
  }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
  // 延迟初始化，确保TinyMCE库已加载
  setTimeout(initTinyMCE, 100);
});
