import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './Parser.css';

const Parser = () => {
  const [status, setStatus] = useState({
    running: false,
    pid: null,
    started_at: null,
    duration: 0,
  });
  
  const [statistics, setStatistics] = useState({
    total_files: 0,
    total_size_mb: 0,
    by_type: {},
  });
  
  const [logs, setLogs] = useState('Логи появятся здесь...');
  const [message, setMessage] = useState({ text: '', type: '' });
  
  const [formData, setFormData] = useState({
    region: 'spb',
    type: 'complexes',
    limit: 100000,
    details: true,
    save_raw: true,
  });

  // Функция для показа сообщений
  const showMessage = (text, type = 'success') => {
    setMessage({ text, type });
    setTimeout(() => setMessage({ text: '', type: '' }), 5000);
  };

  // Обновление статуса
  const updateStatus = async () => {
    try {
      const response = await axios.get('/api/trendagent/parser/status');
      setStatus(response.data);
    } catch (error) {
      console.error('Error updating status:', error);
    }
  };

  // Обновление логов
  const updateLogs = async () => {
    try {
      const response = await axios.get('/api/trendagent/parser/logs?lines=200');
      if (response.data.success) {
        setLogs(response.data.logs);
      }
    } catch (error) {
      // Ignore errors - logs might not exist yet
    }
  };

  // Обновление статистики
  const updateStatistics = async () => {
    try {
      const response = await axios.get('/api/trendagent/parser/statistics');
      setStatistics({
        total_files: response.data.total_files || 0,
        total_size_mb: response.data.total_size_mb || 0,
        by_type: response.data.by_type || {},
      });
    } catch (error) {
      console.error('Error updating statistics:', error);
    }
  };

  // Запуск парсера
  const handleStart = async (e) => {
    e.preventDefault();
    
    try {
      const response = await axios.post('/api/trendagent/parser/start', formData);
      
      if (response.data.success) {
        showMessage('Парсер запущен!', 'success');
        updateStatus();
      }
    } catch (error) {
      showMessage(error.response?.data?.message || 'Ошибка запуска', 'error');
    }
  };

  // Остановка парсера
  const handleStop = async () => {
    try {
      const response = await axios.post('/api/trendagent/parser/stop');
      
      if (response.data.success) {
        showMessage('Парсер остановлен!', 'success');
        updateStatus();
      }
    } catch (error) {
      showMessage(error.response?.data?.message || 'Ошибка остановки', 'error');
    }
  };

  // Автообновление
  useEffect(() => {
    updateStatus();
    updateStatistics();
    
    const interval = setInterval(() => {
      updateStatus();
      updateLogs();
      updateStatistics();
    }, 2000);
    
    return () => clearInterval(interval);
  }, []);

  // Форматирование длительности
  const formatDuration = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours}ч ${minutes}м ${secs}с`;
  };

  return (
    <div className="parser-container">
      <h1>🤖 TrendAgent Parser</h1>
      
      {message.text && (
        <div className={`message ${message.type}`}>
          {message.text}
        </div>
      )}
      
      <div className="grid">
        {/* Status Card */}
        <div className="card">
          <h2>📊 Статус</h2>
          <div className="status-line">
            <span className={`status-indicator ${status.running ? 'running' : 'stopped'}`}></span>
            <span className="status-text">
              {status.running ? 'Парсер запущен' : 'Парсер остановлен'}
            </span>
          </div>
          <div className="status-details">
            {status.running ? (
              <>
                <div>PID: {status.pid}</div>
                <div>Запущен: {status.started_at}</div>
                <div>Длительность: {formatDuration(status.duration)}</div>
              </>
            ) : (
              <div>Парсер не запущен</div>
            )}
          </div>
        </div>
        
        {/* Controls Card */}
        <div className="card">
          <h2>⚙️ Управление</h2>
          <form onSubmit={handleStart}>
            <div className="form-group">
              <label>Регион</label>
              <select 
                value={formData.region}
                onChange={(e) => setFormData({...formData, region: e.target.value})}
              >
                <option value="spb">Санкт-Петербург</option>
                <option value="msk">Москва</option>
              </select>
            </div>
            
            <div className="form-group">
              <label>Тип объектов</label>
              <select
                value={formData.type}
                onChange={(e) => setFormData({...formData, type: e.target.value})}
              >
                <option value="complexes">Комплексы (с квартирами)</option>
                <option value="all">Все типы</option>
                <option value="apartments">Квартиры</option>
                <option value="parkings">Паркинги</option>
                <option value="houses">Дома</option>
                <option value="plots">Участки</option>
                <option value="commercial">Коммерческая</option>
              </select>
            </div>
            
            <div className="form-group">
              <label>Лимит объектов</label>
              <input
                type="number"
                value={formData.limit}
                onChange={(e) => setFormData({...formData, limit: parseInt(e.target.value)})}
                min="1"
              />
            </div>
            
            <div className="form-group">
              <label>
                <input
                  type="checkbox"
                  checked={formData.details}
                  onChange={(e) => setFormData({...formData, details: e.target.checked})}
                />
                Парсить детали (включая все квартиры комплексов)
              </label>
            </div>
            
            <div className="form-group">
              <label>
                <input
                  type="checkbox"
                  checked={formData.save_raw}
                  onChange={(e) => setFormData({...formData, save_raw: e.target.checked})}
                />
                Сохранять сырые данные
              </label>
            </div>
            
            <div className="controls">
              <button 
                type="submit" 
                className="btn btn-primary"
                disabled={status.running}
              >
                ▶️ Запустить
              </button>
              <button 
                type="button"
                className="btn btn-danger"
                onClick={handleStop}
                disabled={!status.running}
              >
                ⏹️ Остановить
              </button>
            </div>
          </form>
        </div>
        
        {/* Statistics Card */}
        <div className="card card-full">
          <h2>📈 Статистика</h2>
          <div className="stats-grid">
            <div className="stat-item">
              <div className="stat-value">{statistics.total_files}</div>
              <div className="stat-label">Всего файлов</div>
            </div>
            <div className="stat-item">
              <div className="stat-value">{statistics.total_size_mb} MB</div>
              <div className="stat-label">Общий размер</div>
            </div>
          </div>
          
          {statistics.by_type && Object.keys(statistics.by_type).length > 0 && (
            <div className="type-stats">
              {Object.entries(statistics.by_type).map(([type, data]) => (
                <div key={type} className="type-stat-item">
                  <strong>{type}:</strong> {data.files} файлов ({data.size_mb} MB)
                </div>
              ))}
            </div>
          )}
        </div>
        
        {/* Logs Card */}
        <div className="card card-full">
          <h2>📋 Логи (обновление каждые 2 сек)</h2>
          <div className="logs">{logs}</div>
        </div>
      </div>
    </div>
  );
};

export default Parser;
