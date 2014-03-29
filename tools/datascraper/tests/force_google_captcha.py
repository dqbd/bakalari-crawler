from selenium import webdriver
from selenium.webdriver.common.keys import Keys
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as ec
from random import randint

driver = webdriver.Firefox()
driver.get("http://google.cz")
assert "Google" in driver.title

def just_do():
    searchbox = driver.find_element_by_xpath('//*[@id="gbqfq"]')
    searchbox.clear()
    searchbox.send_keys(randint(42, 99999))
    searchbox.send_keys(Keys.RETURN)
    time.sleep(1)
    just_do()

def detect_captcha

just_do()