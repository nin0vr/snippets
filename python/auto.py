
import pydirectinput
import random
import time

keys = ['up', 'left', 'right', 'down', 'z', 'x', 'a', 's', 'enter', 'shift', 'q', 'w']

print('1')
time.sleep(1)
print('2')
time.sleep(1)
print('3')
time.sleep(1)
print('4')
time.sleep(1)
print('5')
time.sleep(1)

while True:
    key = random.choice(keys)
    print(key)
    pydirectinput.press(key)
             
             

