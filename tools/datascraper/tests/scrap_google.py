from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

page = 1


def get_results():
    global page, driver
    
    results = WebDriverWait(driver, 10).until(EC.presence_of_all_elements_located((By.CLASS_NAME, "g")))
    
    for result in results:
        link = result.find_element_by_class_name("vurls")
        
        print(link.text)

    try:
        nxt = driver.find_element_by_id("pnnext")
        
        nxt.click()

        page = page + 1
        wait_for_entry()
    except Exception as ex:
        driver.quit()
        pass


def wait_for_entry():
    global driver
    
    while True:
        
        try:
            element = driver.find_element_by_id("resultStats").text
            
            if (("Stránka "+str(page)) in element) or (page == 1):
                get_results()
                break
        except Exception as exc:
            pass


def set_no_filter():
    global driver
    
    while True:
        try:
            driver.find_element_by_id("resultStats")

            url = driver.current_url
            driver.get(url + "&filter=0")

            wait_for_entry()
            
            break
        except:
            pass

    
    
driver = webdriver.Firefox()
driver.get("http://google.cz")
assert "Google" in driver.title
searchbox = driver.find_element_by_xpath('//*[@id="gbqfq"]')
searchbox.send_keys("Bakaláři - přihlášení")
searchbox.send_keys(Keys.RETURN)

set_no_filter()
