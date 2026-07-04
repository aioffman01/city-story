import re

with open('korea_cities.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Replace Header
html = html.replace('<h1>대한민국 10대 도시</h1>', '<h1>부산 10대 식당</h1>')
html = html.replace('<p class="subtitle" style="margin-bottom: 0;">대한민국 주요 10대 도시의 상세 정보, 유명 관광지 및 가장 가까운 국제공항</p>', '<p class="subtitle" style="margin-bottom: 0;">부산을 대표하는 10대 맛집의 상세 정보 및 위치 안내</p>')
html = html.replace('<title>도시이야기 - 대한민국 10대 도시</title>', '<title>도시이야기 - 부산 10대 식당</title>')

js_data = """
        const CITIES_MAP_DATA = {
            rest1: { lat: 35.1158, lng: 129.0401, name: '초량밀면', desc: '물밀면, 비빔밀면', attractions: [{name: '초량밀면', coords: [35.1158, 129.0401]}] },
            rest2: { lat: 35.1342, lng: 129.0945, name: '쌍둥이돼지국밥', desc: '수육백반, 돼지국밥', attractions: [{name: '쌍둥이돼지국밥', coords: [35.1342, 129.0945]}] },
            rest3: { lat: 35.1634, lng: 129.1632, name: '해운대소문난암소갈비', desc: '생갈비, 양념갈비', attractions: [{name: '해운대소문난암소갈비', coords: [35.1634, 129.1632]}] },
            rest4: { lat: 35.0975, lng: 129.0304, name: '백화양곱창', desc: '양곱창 구이', attractions: [{name: '백화양곱창', coords: [35.0975, 129.0304]}] },
            rest5: { lat: 35.1147, lng: 129.0392, name: '신발원', desc: '고기만두, 군만두', attractions: [{name: '신발원', coords: [35.1147, 129.0392]}] },
            rest6: { lat: 35.0934, lng: 129.0403, name: '삼진어묵 본점', desc: '어묵 고로케', attractions: [{name: '삼진어묵 본점', coords: [35.0934, 129.0403]}] },
            rest7: { lat: 35.1018, lng: 129.0315, name: '이재모피자', desc: '이재모 크러스트 피자', attractions: [{name: '이재모피자', coords: [35.1018, 129.0315]}] },
            rest8: { lat: 35.1585, lng: 129.1966, name: '수민이네', desc: '조개구이', attractions: [{name: '수민이네', coords: [35.1585, 129.1966]}] },
            rest9: { lat: 35.1611, lng: 129.1648, name: '금수복국', desc: '복국 (은복, 밀복)', attractions: [{name: '금수복국', coords: [35.1611, 129.1648]}] },
            rest10: { lat: 35.1014, lng: 129.0251, name: '이가네떡볶이', desc: '무채 떡볶이', attractions: [{name: '이가네떡볶이', coords: [35.1014, 129.0251]}] }
        };
"""
html = re.sub(r'const CITIES_MAP_DATA = \{.*?\};', js_data.strip(), html, flags=re.DOTALL)

cards = [
    ("초량밀면", "밀면", "부산역 앞", "물밀면, 비빔밀면, 왕만두", "부산역 앞을 지키는 줄 서는 밀면 맛집", "rest1"),
    ("쌍둥이돼지국밥", "돼지국밥", "대연동", "수육백반, 돼지국밥", "부드러운 수육과 진한 국물의 조화", "rest2"),
    ("해운대소문난암소갈비", "소갈비", "해운대", "생갈비, 양념갈비, 감자사리", "해운대를 대표하는 고급 암소갈비 전문점", "rest3"),
    ("백화양곱창", "양곱창", "자갈치시장", "양곱창 소금구이, 양념구이", "자갈치시장 내 옛날식 양곱창 구이 골목", "rest4"),
    ("신발원", "만두", "부산역 차이나타운", "고기만두, 군만두, 콩국", "백종원의 3대 천왕에도 나온 부산역 앞 만두 맛집", "rest5"),
    ("삼진어묵 본점", "어묵", "영도", "어묵 고로케, 수제 어묵", "1953년부터 이어진 부산에서 가장 오래된 어묵집", "rest6"),
    ("이재모피자", "피자", "남포동", "치즈 크러스트 피자, 파스타", "풍부하고 신선한 임실치즈가 듬뿍 들어간 로컬 피자 맛집", "rest7"),
    ("수민이네", "조개구이", "청사포", "조개구이, 장어구이", "바다를 보며 즐기는 청사포 조개구이의 성지", "rest8"),
    ("금수복국 본점", "복국", "해운대", "은복국, 밀복국", "뚝배기 복국의 원조이자 시원한 국물이 일품인 해장 맛집", "rest9"),
    ("이가네떡볶이", "떡볶이", "부평깡통시장", "무채 떡볶이", "물을 넣지 않고 무에서 나오는 즙으로 만든 진한 떡볶이", "rest10")
]

cards_html = ""
for i, (name, category, location, menu, desc, key) in enumerate(cards):
    cards_html += f'''            <!-- {i+1}. {name} -->
            <div class="city-card">
                <div class="city-card-header">
                    <span class="city-rank-badge">RANK {i+1}</span>
                    <img src="images/busan_rest_{i+1}.jpg" alt="{name}" class="city-image">
                </div>
                <div class="city-card-body">
                    <div class="city-meta">
                        <div class="city-title-group">
                            <h2>{name}</h2>
                            <p class="city-country">{category}</p>
                        </div>
                        <span class="population-badge">{location}</span>
                    </div>
                    <div class="city-details">
                        <div class="detail-item">
                            <span class="detail-label">대표메뉴</span>
                            <span class="detail-val">{menu}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">특징</span>
                            <span class="detail-val">{desc}</span>
                        </div>
                    </div>
                </div>
                <div class="city-card-footer">
                    <button class="btn-primary" onclick="openCityMap('{key}')">지도 보기</button>
                </div>
            </div>\n'''

html = re.sub(r'<main class="city-grid">.*?</main>', f'<main class="city-grid">\n{cards_html}        </main>', html, flags=re.DOTALL)

with open('busan_restaurants.html', 'w', encoding='utf-8') as f:
    f.write(html)
