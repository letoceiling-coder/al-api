import './VillageDetail.css'
import VillageMiniPassport from './VillageMiniPassport'
import VillageReward from './VillageReward'
import VillagePassport from './VillagePassport'
import { getImageUrl, getImageUrlFull } from '../../utils/imageUtils'

const VillageDetail = ({ unifiedData, advantagesData }) => {
  if (!unifiedData) {
    return null
  }

  const getName = () => unifiedData?.name || unifiedData?.village_name || 'Без названия'
  
  const getImages = () => {
    const images = []
    
    if (unifiedData?.images && Array.isArray(unifiedData.images)) {
      unifiedData.images.forEach(img => {
        const imgUrl = getImageUrl(img)
        const imgUrlFull = getImageUrlFull(img)
        if (imgUrl) {
          images.push({
            url: imgUrl,
            urlFull: imgUrlFull || imgUrl,
          })
        }
      })
    }
    
    return images
  }

  const images = getImages()

  return (
    <div className="village-detail-page">
      {/* Заголовок */}
      <div className="village-header container">
        <h1 className="village-header-title">{getName()}</h1>
      </div>

      {/* Основная секция с галереей и мини-паспортом */}
      <div className="main-info container">
        <div className="page-layout__row">
          <div className="page-layout__col page-layout__col--lg main-info__left">
            {/* Галерея изображений */}
            {images.length > 0 && (
              <div className="apartment-photogallery">
                <div className="gallery gallery_border gallery_navigation gallery_navigation-inside">
                  <div className="gallery__slider">
                    <div className="gallery__zoom">
                      <button className="btn btn_white btn_onlyicon" type="button">
                        <span className="btn__content">
                          <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                            <path fillRule="evenodd" clipRule="evenodd" d="M11.5001 2.75C11.5001 2.33579 11.8359 2 12.2501 2H17.2501C17.6643 2 18.0001 2.33579 18.0001 2.75V7.75C18.0001 8.16421 17.6643 8.5 17.2501 8.5C16.8359 8.5 16.5001 8.16421 16.5001 7.75V4.56066L12.2805 8.78033C11.9876 9.07322 11.5127 9.07322 11.2198 8.78033C10.9269 8.48744 10.9269 8.01256 11.2198 7.71967L15.4395 3.5H12.2501C11.8359 3.5 11.5001 3.16421 11.5001 2.75ZM8.78045 11.2197C9.07335 11.5126 9.07335 11.9874 8.78045 12.2803L4.56078 16.5H7.75012C8.16434 16.5 8.50012 16.8358 8.50012 17.25C8.50012 17.6642 8.16434 18 7.75012 18H2.75012C2.33591 18 2.00012 17.6642 2.00012 17.25V12.25C2.00012 11.8358 2.33591 11.5 2.75012 11.5C3.16434 11.5 3.50012 11.8358 3.50012 12.25V15.4393L7.71979 11.2197C8.01269 10.9268 8.48756 10.9268 8.78045 11.2197Z" fill="#4C4C4C"/>
                          </svg>
                        </span>
                      </button>
                    </div>
                    <div className="glide">
                      <div className="glide__track">
                        <ul className="glide__slides">
                          {images.map((img, index) => (
                            <li key={index} className="glide__slide">
                              <div className="gallery__item">
                                <div className="apartment-image_container">
                                  <img src={img.urlFull || img.url} alt={`${getName()} - ${index + 1}`} />
                                </div>
                              </div>
                            </li>
                          ))}
                        </ul>
                      </div>
                      <div className="glide__arrow glide__arrow_prev">
                        <button className="btn btn_white btn_onlyicon" type="button">
                          <span className="btn__content">
                            <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                              <path fillRule="evenodd" clipRule="evenodd" d="M7.50012 10.25C7.50012 10.4489 7.57914 10.6397 7.71979 10.7803L11.2198 14.2803C11.5127 14.5732 11.9876 14.5732 12.2805 14.2803C12.5733 13.9874 12.5733 13.5126 12.2805 13.2197L9.31078 10.25L12.2805 7.28033C12.5733 6.98744 12.5733 6.51256 12.2805 6.21967C11.9876 5.92678 11.5127 5.92678 11.2198 6.21967L7.71979 9.71967C7.57914 9.86032 7.50012 10.0511 7.50012 10.25Z" fill="#4C4C4C"/>
                            </svg>
                          </span>
                        </button>
                      </div>
                      <div className="glide__arrow glide__arrow_next">
                        <button className="btn btn_white btn_onlyicon" type="button">
                          <span className="btn__content">
                            <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                              <path fillRule="evenodd" clipRule="evenodd" d="M12.5 10.25C12.5 10.0511 12.421 9.86032 12.2803 9.71967L8.78033 6.21967C8.48744 5.92678 8.01256 5.92678 7.71967 6.21967C7.42678 6.51256 7.42678 6.98744 7.71967 7.28033L10.6893 10.25L7.71967 13.2197C7.42678 13.5126 7.42678 13.9874 7.71967 14.2803C8.01256 14.5732 8.48744 14.5732 8.78033 14.2803L12.2803 10.7803C12.421 10.6397 12.5 10.4489 12.5 10.25Z" fill="#4C4C4C"/>
                            </svg>
                          </span>
                        </button>
                      </div>
                    </div>
                    <div className="gallery__navigation gallery__navigation_inside">
                      <div className="gallery-nav">
                        <div className="gallery-nav__menu" role="menu">
                          {images.slice(0, 4).map((img, index) => (
                            <div key={index} role="menuitem" className="gallery-nav__item">
                              <div className="gallery-nav__item-inner">
                                <div className="apartment-image_container">
                                  <img src={img.url} alt={`${getName()} - ${index + 1}`} />
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                        <div className="gallery-nav__right-side">
                          <div className="apartment-photogallery__list_btns">
                            <div className="apartment-photogallery__slider_btn">
                              <button className="btn btn_secondary px-4" type="button">
                                <span className="btn__content">План посёлка</span>
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}
            
            {/* Мини-паспорт - показываем только если есть данные */}
            {(unifiedData?.address || unifiedData?.distance) && (
              <div className="mini-passport__container">
                <div className="mini-passport__top">
                  <VillageMiniPassport unifiedData={unifiedData} distance={unifiedData?.distance} />
                </div>
              </div>
            )}
          </div>

          {/* Правая колонка */}
          <div className="page-layout__col page-layout__col--sm main-info__right">
            {/* Вознаграждения - показываем только если есть данные */}
            {unifiedData?.reward && (
              <VillageReward rewardData={unifiedData?.reward} />
            )}
            
            {/* Фиксация клиента - всегда показываем */}
            <div className="fixation-block__container">
              <div className="fixation-block__block fixation-block__block_container">
                <div className="fixation-block__block fixation-block__block_icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#667eea"/>
                  </svg>
                </div>
                <div className="fixation-block__block fixation-block__block_info">
                  <h6>Фиксация клиента</h6>
                  <p>Задайте уточняющие вопросы, запишитесь на встречу или зафиксируйте клиента</p>
                </div>
              </div>
              <div className="fixation-block__button">
                <button className="btn btn_brand px-4" type="button">
                  <span className="btn__content">Зафиксировать у застройщика</span>
                </button>
              </div>
            </div>

            {/* Паспорт объекта - показываем только если есть данные */}
            {(unifiedData?.passport || unifiedData?.builder || unifiedData?.village_class) && (
              <VillagePassport unifiedData={unifiedData} />
            )}
          </div>
        </div>
        <div className="main-info__boundary"></div>
      </div>
    </div>
  )
}

export default VillageDetail
