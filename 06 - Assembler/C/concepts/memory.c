#include <stdio.h>

int main() {
    char str[] = "hello";
    char *p = str;
    
    printf("%c\n", *p);       // h
    printf("%c\n", *(p + 1)); // e
    printf("%c\n", *(p + 4)); // o
    return 0;
}