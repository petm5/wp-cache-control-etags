NAME  = cache-posts-and-pages
ENTRY = $(NAME).php
FILES = $(ENTRY) readme.txt

VERSION := $(shell grep "^ \* Version" $(ENTRY) | awk -F' ' '{print $3}' | cut -d ":" -f2 | sed 's/ //g')
TARGET  := $(NAME)-$(VERSION).zip

all: build

build: clean
	mkdir -p build/$(NAME) && cp -av $(FILES) build/$(NAME)
	cd build && zip -r $(TARGET) *
	mv -v build/$(TARGET) ./

clean:
	rm -rf build $(TARGET)
