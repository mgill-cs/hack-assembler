# Takes the parsed instruction and translates to binary

import symbol_table

COMP = {
    "0": "0101010",
    "1": "0111111",
    "-1": "0111010",
    "D": "0001100",
    "A": "0110000",
    "M": "1110000",
    "!D": "0001101",
    "!A": "0110001",
    "!M": "1110001",
    "-D": "0001111",
    "-A": "0110011",
    "-M": "1110011",
    "D+1": "0011111",
    "A+1": "0110111",
    "M+1": "1110111",
    "D-1": "0001110",
    "A-1": "0110010",
    "M-1": "1110010",
    "D+A": "0000010",
    "D+M": "1000010",
    "D-A": "0010011",
    "D-M": "1010011",
    "A-D": "0000111",
    "M-D": "1000111",
    "D&A": "0000000",
    "D&M": "1000000",
    "D|A": "0010101",
    "D|M": "1010101",
}
DEST = {
    "": "000",
    "M": "001",
    "D": "010",
    "DM": "011",
    "MD": "011",
    "A": "100",
    "AM": "101",
    "MA": "101",
    "AD": "110",
    "DA": "110",
    "ADM": "111",
    "AMD": "111",
    "DAM": "111",
    "DMA": "111",
    "MDA": "111",
    "MAD": "111",
}
JUMP = {
    "": "000",
    "JGT": "001",
    "JEQ": "010",
    "JGE": "011",
    "JLT": "100",
    "JNE": "101",
    "JLE": "110",
    "JMP": "111",
}


# Translates Instruction to binary
def atranslate(instruction):
    if instruction.startswith("@"):
        if not instruction[1:].isdigit():
            a = bin(int(symbol_table.lookup(instruction[1:])))[2:]
            a = a.zfill(16)
        else:
            a = bin(int(instruction[1:]))[2:]
            a = a.zfill(16)
        return a
    else:
        return None


def ctranslate(instruction):
    if instruction.startswith("("):
        return None
    if "=" in instruction and ";" in instruction:
        d = instruction[: instruction.find("=")]
        c = instruction[instruction.find("=") + 1 : instruction.find(";")]
        j = instruction[instruction.find(";") + 1 :]
    elif ";" in instruction:
        d = ""
        c = instruction[: instruction.find(";")]
        j = instruction[instruction.find(";") + 1 :]
    elif "=" in instruction:
        d = instruction[: instruction.find("=")]
        c = instruction[instruction.find("=") + 1 :]
        j = ""
    else:
        d = ""
        c = instruction
        j = ""
    d = DEST[d]
    c = COMP[c]
    j = JUMP[j]
    return "111" + c + d + j


def translate(instruction):
    translated = []
    for i in instruction:
        if i.startswith("@"):
            a = atranslate(i)
            translated.append(a)
        else:
            c = ctranslate(i)
            if c is not None:
                translated.append(c)
    return translated
