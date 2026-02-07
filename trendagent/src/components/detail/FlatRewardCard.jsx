import './FlatRewardCard.css'

const FlatRewardCard = ({ rewardsData }) => {
  if (!rewardsData || !rewardsData.data) {
    return null
  }

  const reward = Array.isArray(rewardsData.data) ? rewardsData.data[0] : rewardsData.data
  
  if (!reward) {
    return null
  }

  const rewardValue = reward.value || reward.percent || reward.percentage || '—'
  const rewardType = reward.type || reward.scale || 'Фикс'
  const paymentTime = reward.payment_time || reward.payment || '4 часа после сверки'

  return (
    <div className="reward-card-component">
      <div className="reward-card-component__section reward-card-component__section_container">
        <div className="reward-card-component__section reward-card-component__section_content reward-card-component__section_content--only">
          <div>
            <div className="reward-card-component__section reward-card-component__section_data">
              <div className="reward-card-component__section">
                <div className="reward-card-component__section reward-card-component__section_reward--container">
                  <div className="reward-card-component__section reward-card-component__section_reward--title">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <rect width="30" height="30" rx="10" fill="url(#paint0_linear_14563_72850)"></rect>
                      <path fillRule="evenodd" clipRule="evenodd" d="M24.1172 18.9453C24.1748 18.2407 24.1748 17.3646 24.1748 16.2568V13.1504L24.1747 12.8023H5.00004L5 13.0993V13.1504V16.2568V16.3088C4.99999 17.392 4.99997 18.2517 5.05664 18.9453C5.11493 19.6587 5.23675 20.2623 5.51758 20.8135C5.97298 21.7072 6.69998 22.4342 7.59375 22.8896C8.14484 23.1704 8.74866 23.2913 9.46191 23.3496C10.1665 23.4072 11.0427 23.4072 12.1504 23.4072H17.0244C18.1321 23.4072 19.0083 23.4072 19.7129 23.3496C20.4262 23.2913 21.0299 23.1705 21.5811 22.8896C22.4748 22.4343 23.2008 21.7071 23.6562 20.8135C23.9371 20.2623 24.0589 19.6587 24.1172 18.9453ZM24.1172 10.4619C24.1381 10.7184 24.1515 10.9975 24.16 11.3023H5.01417C5.02249 10.9975 5.03569 10.7184 5.05664 10.4619C5.11492 9.74862 5.23682 9.14487 5.51758 8.59375C5.97298 7.69998 6.69998 6.97298 7.59375 6.51758C8.14487 6.23682 8.74862 6.11492 9.46191 6.05664C10.1556 5.99997 11.0158 5.99999 12.0993 6H12.1504H17.0244H17.0764C18.1596 5.99999 19.0193 5.99997 19.7129 6.05664C20.4262 6.11493 21.0299 6.23676 21.5811 6.51758C22.4748 6.97298 23.2009 7.70002 23.6562 8.59375C23.9371 9.14492 24.0589 9.74853 24.1172 10.4619ZM13.5703 15.5872C13.5703 15.173 13.2344 14.8373 12.8203 14.8372H9.28516C8.87094 14.8372 8.53516 15.1729 8.53516 15.5872C8.53516 16.0014 8.87094 16.3372 9.28516 16.3372H12.8203C13.2344 16.337 13.5703 16.0013 13.5703 15.5872Z" fill="url(#paint1_linear_14563_72850)"></path>
                      <defs>
                        <linearGradient id="paint0_linear_14563_72850" x1="4.5" y1="4.5" x2="25.65" y2="25.65" gradientUnits="userSpaceOnUse">
                          <stop stopColor="#EDF6FE"></stop>
                          <stop offset="1" stopColor="#F5EEFE"></stop>
                        </linearGradient>
                        <linearGradient id="paint1_linear_14563_72850" x1="7.87622" y1="8.61108" x2="20.0911" y2="22.0663" gradientUnits="userSpaceOnUse">
                          <stop stopColor="#4DA7F8"></stop>
                          <stop offset="1" stopColor="#9B54F2"></stop>
                        </linearGradient>
                      </defs>
                    </svg>
                    <span className="reward-card-component__reward reward-card-component__reward_label reward-card-component__reward_prewrap">Ваше вознаграждение</span>
                  </div>
                  <span className="reward-card-component__section reward-card-component__section_reward--value reward-card-component__section_reward--main">{rewardValue}</span>
                </div>
                <div className="reward-card-component__section reward-card-component__section_reward--data reward-card-component__section_reward--divDer">
                  <div data-testid="reward-card-compensation" className="reward-card-component__section reward-card-component__section_reward--compensation">
                    <span className="reward-card-component__section reward-card-component__section_reward--label">Шкала</span>
                    <span className="reward-card-component__section reward-card-component__section_reward--value">{rewardType}</span>
                  </div>
                  <div data-testid="reward-card-payment" className="reward-card-component__section reward-card-component__section_reward--payment">
                    <span className="reward-card-component__section reward-card-component__section_reward--label">Быстрые выплаты</span>
                    <span className="reward-card-component__section reward-card-component__section_reward--value">{paymentTime}</span>
                  </div>
                </div>
              </div>
              <div data-testid="reward-card-button" className="shell-element shell-element_md shell-element_secondary shell-element_radius-lg shell-element_radius btn-wrapper btn-wrapper_press-effect-animation reward-card-component__section reward-card-component__section_reward--go-table">
                <button tabIndex="0" className="btn btn_secondary px-4" type="button">
                  <span className="btn__content justify-content-center">
                    <span>Перейти в таблицу вознаграждений</span>
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default FlatRewardCard
