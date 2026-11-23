TARGET = cache-posts-and-pages.zip
FILES  = cache-posts-and-pages.php readme.txt

all: build

build: clean
	zip -r $(TARGET) $(FILES)

clean:
	rm -f $(TARGET)
