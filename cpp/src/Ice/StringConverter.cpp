// **********************************************************************
//
// Copyright (c) 2003-2008 ZeroC, Inc. All rights reserved.
//
// This copy of Ice is licensed to you under the terms described in the
// ICE_LICENSE file included in this distribution.
//
// **********************************************************************

#include <Ice/StringConverter.h>
#include <IceUtil/IceUtil.h>
#include <IceUtil/StringUtil.h>
#include <IceUtil/ScopedArray.h>
#include <Ice/LocalException.h>

using namespace IceUtil;
using namespace IceUtilInternal;
using namespace std;

namespace Ice
{

UnicodeWstringConverter::UnicodeWstringConverter(ConversionFlags flags) :
    _conversionFlags(flags)
{
}

Byte* 
UnicodeWstringConverter::toUTF8(const wchar_t* sourceStart, 
                                const wchar_t* sourceEnd,
                                UTF8Buffer& buffer) const
{
    //
    // The "chunk size" is the maximum of the number of characters in the
    // source and 6 (== max bytes necessary to encode one Unicode character).
    //
    size_t chunkSize = std::max<size_t>(static_cast<size_t>(sourceEnd - sourceStart), 6);

    Byte* targetStart = buffer.getMoreBytes(chunkSize, 0);
    Byte* targetEnd = targetStart + chunkSize;

    ConversionResult result;

    while((result =
          convertUTFWstringToUTF8(sourceStart, sourceEnd, 
                                  targetStart, targetEnd, _conversionFlags))
          == targetExhausted)
    {
        targetStart = buffer.getMoreBytes(chunkSize, targetStart);
        targetEnd = targetStart + chunkSize;
    }
        
    switch(result)
    {
        case conversionOK:
            break;
        case sourceExhausted:
            throw StringConversionException(__FILE__, __LINE__, "wide string source exhausted");
        case sourceIllegal:
            throw StringConversionException(__FILE__, __LINE__, "wide string source illegal");
        default:
        {
            assert(0);
            throw StringConversionException(__FILE__, __LINE__);
        }
    }
    return targetStart;
}


void 
UnicodeWstringConverter::fromUTF8(const Byte* sourceStart, const Byte* sourceEnd,
                                  wstring& target) const
{
    if(sourceStart == sourceEnd)
    {
        target = L"";
        return;
    }

    ConversionResult result = 
        convertUTF8ToUTFWstring(sourceStart, sourceEnd, target, _conversionFlags);

    switch(result)
    {    
        case conversionOK:
            break;
        case sourceExhausted:
            throw StringConversionException(__FILE__, __LINE__, "UTF-8 string source exhausted");
        case sourceIllegal:
            throw StringConversionException(__FILE__, __LINE__, "UTF-8 string source illegal");
        default:
        {
            assert(0);
            throw StringConversionException(__FILE__, __LINE__);
        }
    }
}

#ifdef _WIN32
WindowsStringConverter::WindowsStringConverter(unsigned int cp) :
    _cp(cp)
{
}

Byte*
WindowsStringConverter::toUTF8(const char* sourceStart,
                               const char* sourceEnd,
                               UTF8Buffer& buffer) const
{
    //
    // First convert to UTF-16
    //
    int sourceSize = static_cast<int>(sourceEnd - sourceStart);
    if(sourceSize == 0)
    {
        return buffer.getMoreBytes(1, 0);
    }

    int size = 0;
    int writtenWchar = 0;
    ScopedArray<wchar_t> wbuffer;
    do
    {
        size = size == 0 ? sourceSize + 2 : 2 * size;
        wbuffer.reset(new wchar_t[size]);

        writtenWchar = MultiByteToWideChar(_cp, MB_ERR_INVALID_CHARS, sourceStart,
                                           sourceSize, wbuffer.get(), size);
    } while(writtenWchar == 0 && GetLastError() == ERROR_INSUFFICIENT_BUFFER);

    if(writtenWchar == 0)
    {
        throw StringConversionException(__FILE__, __LINE__, IceUtilInternal::lastErrorToString());
    }

    //
    // Then convert this UTF-16 wbuffer into UTF-8
    //
    return _unicodeWstringConverter.toUTF8(wbuffer.get(), wbuffer.get() + writtenWchar, buffer);
}

void
WindowsStringConverter::fromUTF8(const Byte* sourceStart, const Byte* sourceEnd,
                                 string& target) const
{
    if(sourceStart == sourceEnd)
    {
        target = "";
        return;
    }

    //
    // First convert to wstring (UTF-16)
    //
    wstring wtarget;
    _unicodeWstringConverter.fromUTF8(sourceStart, sourceEnd, wtarget);

    //
    // And then to a multi-byte narrow string
    //
    int size = 0;
    int writtenChar = 0;
    ScopedArray<char> buffer;
    do
    {
        size = size == 0 ? static_cast<int>(sourceEnd - sourceStart) + 2 : 2 * size;
        buffer.reset(new char[size]);
        writtenChar = WideCharToMultiByte(_cp, 0, wtarget.data(), static_cast<int>(wtarget.size()),
                                          buffer.get(), size, 0, 0);
    } while(writtenChar == 0 && GetLastError() == ERROR_INSUFFICIENT_BUFFER);

    if(writtenChar == 0)
    {
        throw StringConversionException(__FILE__, __LINE__, IceUtilInternal::lastErrorToString());
    }

    target.assign(buffer.get(), writtenChar);
}

#endif

}
