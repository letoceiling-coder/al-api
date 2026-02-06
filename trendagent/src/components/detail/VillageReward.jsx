import './VillageReward.css'

const VillageReward = ({ rewardData }) => {
  if (!rewardData) {
    return null
  }

  // Безопасное извлечение reward и hint
  let reward = null
  let hint = null
  
  if (typeof rewardData === 'string') {
    reward = rewardData
  } else if (typeof rewardData === 'object' && rewardData !== null) {
    reward = rewardData.label || rewardData.value || null
    hint = rewardData.hint || null
  }
  
  if (!reward) {
    return null
  }

  return (
    <div className="village-reward">
      <h3>Вознаграждения</h3>
      <div className="reward-content">
        <div className="reward-main">
          <div className="reward-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="none">
              <rect width="30" height="30" rx="10" fill="url(#paint0_linear)"/>
              <path fillRule="evenodd" clipRule="evenodd" d="M24.1172 18.9453C24.1748 18.2407 24.1748 17.3646 24.1748 16.2568V13.1504L24.1747 12.8023H5.00004L5 13.0993V13.1504V16.2568V16.3088C4.99999 17.392 4.99997 18.2517 5.05664 18.9453C5.11493 19.6587 5.23675 20.2623 5.51758 20.8135C5.97298 21.7072 6.69998 22.4342 7.59375 22.8896C8.14484 23.1704 8.74866 23.2913 9.46191 23.3496C10.1665 23.4072 11.0427 23.4072 12.1504 23.4072H17.0244C18.1321 23.4072 19.0083 23.4072 19.7129 23.3496C20.4262 23.2913 21.0299 23.1705 21.5811 22.8896C22.4748 22.4343 23.2008 21.7071 23.6562 20.8135C23.9371 20.2623 24.0589 19.6587 24.1172 18.9453Z" fill="url(#paint1_linear)"/>
              <defs>
                <linearGradient id="paint0_linear" x1="4.5" y1="4.5" x2="25.65" y2="25.65">
                  <stop stopColor="#EDF6FE"/>
                  <stop offset="1" stopColor="#F5EEFE"/>
                </linearGradient>
                <linearGradient id="paint1_linear" x1="7.87622" y1="8.61108" x2="20.0911" y2="22.0663">
                  <stop stopColor="#4DA7F8"/>
                  <stop offset="1" stopColor="#9B54F2"/>
                </linearGradient>
              </defs>
            </svg>
          </div>
          <div className="reward-label">Ваше вознаграждение</div>
          <div className="reward-value">{reward}</div>
          {hint && (
            <div className="reward-hint">
              <span className="reward-hint-label">Шкала</span>
              <span className="reward-hint-value">{hint}</span>
            </div>
          )}
        </div>
        <button className="btn btn-secondary reward-button">
          Перейти в таблицу вознаграждений
        </button>
      </div>
    </div>
  )
}

export default VillageReward
