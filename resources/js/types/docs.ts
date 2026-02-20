export interface DocPageLink {
    title: string;
    url: string;
}

export interface DocPage {
    title: string;
    introduction: string;
    htmlContent: string;
    slug: string;
    headings: DocHeading[];
    previousPage: DocPageLink | null;
    nextPage: DocPageLink | null;
}

export interface DocHeading {
    text: string;
    slug: string;
    level: number;
}

export interface DocChapter {
    title: string;
    slug: string;
    pages: DocChapterPage[];
}

export interface DocChapterPage {
    title: string;
    slug: string;
}

export interface DocSearchResult {
    title: string;
    introduction: string;
    chapterTitle: string;
    url: string;
}

export interface DocShowProps {
    page: DocPage;
    chapters: DocChapter[];
    currentChapter: string;
    currentPage: string;
}

export interface DocSearchProps {
    results: DocSearchResult[];
    query: string;
    chapters: DocChapter[];
}
