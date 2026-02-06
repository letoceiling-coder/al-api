import './VillageMiniPassport.css'

const VillageMiniPassport = ({ unifiedData, distance }) => {
  if (!unifiedData) {
    return null
  }

  const getMiniPassportData = () => {
    const data = unifiedData || {}
    const dist = distance || {}
    
    return {
      address: data.address || null,
      centerDistance: dist.center || data.distance?.center || null,
      railway: dist.railway || data.distance?.railway || null,
      highway: dist.highway || data.distance?.highway || null,
    }
  }

  const mini = getMiniPassportData()
  const items = []

  if (mini.address) {
    items.push({
      icon: 'map-pin',
      value: mini.address,
    })
  }

  if (mini.centerDistance) {
    items.push({
      icon: 'dot',
      value: `До центра, ${mini.centerDistance}`,
    })
  }

  if (mini.railway) {
    items.push({
      icon: 'train',
      value: mini.railway,
    })
  }

  if (mini.highway) {
    items.push({
      icon: 'road',
      value: mini.highway,
    })
  }

  if (items.length === 0) {
    return null
  }

  return (
    <div className="village-mini-passport">
      {items.map((item, index) => (
        <div key={index} className="mini-passport-item">
          <span className="mini-passport-icon">
            {item.icon === 'map-pin' && (
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path fillRule="evenodd" clipRule="evenodd" d="M10 2.5C8.55604 2.5 7.16142 3.12536 6.12594 4.25498C5.08912 5.38605 4.5 6.92874 4.5 8.54545C4.5 10.9058 5.90006 13.1527 7.37752 14.854C8.10837 15.6956 8.84061 16.3836 9.3904 16.8612C9.63643 17.0749 9.8452 17.2459 10 17.3689C10.1548 17.2459 10.3636 17.0749 10.6096 16.8612C11.1594 16.3836 11.8916 15.6956 12.6225 14.854C14.0999 13.1527 15.5 10.9058 15.5 8.54545C15.5 6.92874 14.9109 5.38605 13.8741 4.25498C12.8386 3.12536 11.444 2.5 10 2.5Z" fill="#4C4C4C"/>
              </svg>
            )}
            {item.icon === 'dot' && (
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <circle cx="10" cy="10" r="4" fill="#52525B"/>
              </svg>
            )}
            {item.icon === 'train' && (
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path fillRule="evenodd" clipRule="evenodd" d="M13.0671 4.56793C12.8987 4.50697 12.7095 4.50006 12.0878 4.50006H5.09091C4.30538 4.50006 3.76654 4.50112 3.362 4.55551C2.97184 4.60797 2.78227 4.70164 2.65102 4.83289C2.51976 4.96415 2.42609 5.15372 2.37363 5.54388C2.31924 5.94842 2.31818 6.48726 2.31818 7.27279V8.09097C2.31818 8.87649 2.31924 9.41534 2.37363 9.81988C2.42609 10.21 2.51976 10.3996 2.65102 10.5309C2.78227 10.6621 2.97184 10.7558 3.362 10.8082C3.76654 10.8626 4.30539 10.8637 5.09091 10.8637H15.953C16.5392 10.8637 16.7254 10.8586 16.8642 10.8181C17.2364 10.7093 17.5274 10.4183 17.6362 10.0461C17.6768 9.90731 17.6818 9.72108 17.6818 9.13489C17.6818 8.84708 17.6803 8.75792 17.6676 8.67794C17.6339 8.46753 17.5418 8.27082 17.4017 8.11026C17.3484 8.04924 17.2809 7.99103 17.0598 7.80678L16.0372 6.95461H11.6364V5.95461H14.8372L13.8629 5.14272C13.3853 4.74476 13.2355 4.6289 13.0671 4.56793Z" fill="#52525B"/>
              </svg>
            )}
            {item.icon === 'road' && (
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path fillRule="evenodd" clipRule="evenodd" d="M5.46472 2H14.5353C15.2123 1.99998 15.7707 1.99997 16.2125 2.05938C16.6764 2.12175 17.0862 2.25773 17.4142 2.58579C17.7423 2.91384 17.8783 3.32355 17.9406 3.78747C18 4.22935 18 4.78766 18 5.46473V14.5353C18 15.2123 18 15.7707 17.9406 16.2125C17.8783 16.6764 17.7423 17.0862 17.4142 17.4142C17.0862 17.7423 16.6764 17.8783 16.2125 17.9406C15.7707 18 15.2123 18 14.5353 18H5.46473C4.78766 18 4.22935 18 3.78747 17.9406C3.32355 17.8783 2.91384 17.7423 2.58579 17.4142C2.25773 17.0862 2.12175 16.6764 2.05938 16.2125C1.99997 15.7707 1.99998 15.2123 2 14.5353V5.46472C1.99998 4.78766 1.99997 4.22935 2.05938 3.78747C2.12175 3.32355 2.25773 2.91384 2.58579 2.58579C2.91384 2.25773 3.32355 2.12175 3.78747 2.05938C4.22935 1.99997 4.78766 1.99998 5.46472 2Z" fill="#52525B"/>
              </svg>
            )}
          </span>
          <span className="mini-passport-value">{item.value}</span>
        </div>
      ))}
    </div>
  )
}

export default VillageMiniPassport
