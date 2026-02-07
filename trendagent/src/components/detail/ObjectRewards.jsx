import './ObjectRewards.css'

const ObjectRewards = ({ rewardsData }) => {
  if (!rewardsData || !rewardsData.data) {
    return null
  }

  const rewards = Array.isArray(rewardsData.data) ? rewardsData.data : [rewardsData.data]

  if (rewards.length === 0) {
    return null
  }

  return (
    <div className="object-rewards">
      <h2>Вознаграждения</h2>
      <div className="rewards-content">
        {rewards.map((reward, index) => (
          <div key={index} className="reward-item">
            {reward.label && <div className="reward-label">{reward.label}</div>}
            {reward.hint && <div className="reward-hint">{reward.hint}</div>}
            {reward.value && <div className="reward-value">{reward.value}</div>}
          </div>
        ))}
      </div>
    </div>
  )
}

export default ObjectRewards
