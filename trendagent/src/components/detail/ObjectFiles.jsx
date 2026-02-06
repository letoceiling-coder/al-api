import './ObjectFiles.css'

const ObjectFiles = ({ files }) => {
  const filesList = files || []

  if (!filesList || filesList.length === 0) {
    return (
      <div className="object-files card">
        <h2 className="section-title">Файлы</h2>
        <div className="empty-state">
          <p>Файлы отсутствуют</p>
        </div>
      </div>
    )
  }

  const getFileUrl = (file) => {
    if (file.url) return file.url
    if (file.file_name && file.path) {
      return `https://selcdn.trendagent.ru/files/${file.path}${file.file_name}`
    }
    return null
  }

  const getFileIcon = (fileName) => {
    const ext = fileName?.split('.').pop()?.toLowerCase()
    if (['pdf'].includes(ext)) return '📄'
    if (['doc', 'docx'].includes(ext)) return '📝'
    if (['xls', 'xlsx'].includes(ext)) return '📊'
    if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return '🖼️'
    return '📎'
  }

  return (
    <div className="object-files card">
      <h2 className="section-title">Файлы</h2>
      <div className="files-list">
        {filesList.map((file, index) => {
          const fileUrl = getFileUrl(file)
          const fileName = file.name || file.file_name || `Файл ${index + 1}`
          
          return (
            <a
              key={file._id || index}
              href={fileUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="file-item"
            >
              <span className="file-icon">{getFileIcon(fileName)}</span>
              <div className="file-content">
                <span className="file-name">{fileName}</span>
                {file.size && (
                  <span className="file-size">{formatFileSize(file.size)}</span>
                )}
              </div>
              <svg className="file-download-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </a>
          )
        })}
      </div>
    </div>
  )
}

const formatFileSize = (bytes) => {
  if (!bytes) return ''
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export default ObjectFiles
